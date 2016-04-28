/* vim: set expandtab ts=4 sw=4: */
/*
 * You may redistribute this program and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
#include <unistd.h>
#include <stdlib.h>
#include <stdio.h>
#include <errno.h>
#include <sys/types.h>
#include <sysexits.h>
#include <string.h>

#include "util/Hex.h"
#include "memory/Allocator.h"
#include "memory/MallocAllocator.h"
#include "crypto/random/Random.h"
#include "io/FileReader.h"
#include "io/FileWriter.h"
#include "util/CString.h"
#include "util/Assert.h"
#include "util/log/Log.h"
#include "util/log/FileWriterLog.h"
#include "util/events/Time.h"
#include "util/events/EventBase.h"
#include "util/events/Pipe.h"
#include "util/events/Process.h"
#include "admin/angel/InterfaceWaiter.h"

static void onCoreExit(int64_t exit_status, int term_signal)
{
    Assert_failure("Core exited with status [%d], signal [%d]\n", (int)exit_status, term_signal);
}

/**
 * Usage:
 *  pass an absolute path to cjdroute executable as an argument
 *  and this program will act as inetd boostrap,
 *  capturing data comming from STDIN and passing it as inital core config
 *  of cjdroute core and sending response on STDOUT.
 **/

int main(int argc, char** argv)
{
    if (argc != 2) {
        exit(EX_USAGE);
    }
    char* corePath = argv[1];

    struct Except* eh = NULL;

    // Allow it to allocate 1MB
    struct Allocator* allocator = MallocAllocator_new(1<<20);
    struct Log* logger = NULL; // We don't want messages from the trap.
    struct Random* rand = Random_new(allocator, logger, eh);
    struct EventBase* eventBase = EventBase_new(allocator);

    struct Writer* stdoutWriter = FileWriter_new(stdout, allocator);

    struct Allocator* corePipeAlloc = Allocator_child(allocator);
    char corePipeName[64] = "client-core-";
    Random_base32(rand, (uint8_t*)corePipeName+CString_strlen(corePipeName), 31);
    Assert_ifParanoid(EventBase_eventCount(eventBase) == 0);
    struct Pipe* corePipe = Pipe_named(corePipeName, eventBase, eh, corePipeAlloc);
    corePipe->logger = logger;
    Assert_ifParanoid(EventBase_eventCount(eventBase) == 2);

    struct Message* toCoreMsg = Message_new(0, 1024, allocator);
    unsigned char buff[1024] = { 0 };
    int len;
    do {
        len = read(STDIN_FILENO, buff, 1024);
        if (len <= 0 && errno != EAGAIN) {
            fprintf(stderr, "Read returned: %d with errno %s.\n", len, strerror(errno));
            exit(EX_NOINPUT);
        }
        // read will return -1 and set errno if there is nonblocking pipe.
    } while (len == -1 && errno == EAGAIN);

    Message_push(toCoreMsg, buff, len, eh);

    char* args[] = { "core", corePipeName, NULL };

    Process_spawn(corePath, args, eventBase, allocator, onCoreExit);


    Iface_CALL(corePipe->iface.send, toCoreMsg, &corePipe->iface);

    struct Message* fromCoreMsg =
                InterfaceWaiter_waitForData(&corePipe->iface, eventBase, allocator, eh);
    Writer_write(stdoutWriter, fromCoreMsg->bytes, fromCoreMsg->length);

    return 0;
}
