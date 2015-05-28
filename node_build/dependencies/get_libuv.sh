#!/usr/bin/env bash
# You may redistribute this program and/or modify it under the terms of
# the GNU General Public License as published by the Free Software Foundation,
# either version 3 of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

# script meant to be run manually, for getting a deterministic copy of libuv for cjdns.
# After running:
# $ cd libuv
# $ find ./ -type f -exec sha256sum {} \; | sort | sha256sum
#   96d6a3a5950f530bf7a2f17db74ba1c06118457bc22959020ec23c58d33a11ed  -


die() {
    echo "ERROR: $1";
    exit 100;
}

git clone --depth=1 --branch=v1.5.0 https://github.com/libuv/libuv.git || die 'clone libuv'
cd libuv || die 'cd libuv'
git checkout 4e77f74c7b95b639b3397095db1bc5bcc016c203 || die 'checkout revision of libuv'
rm -rf ./.git || die 'rm -rf ./.git'
sed -i 's/\/build\/gyp//' .gitignore
mkdir -p build || die 'mkdir -p build'
git clone https://chromium.googlesource.com/external/gyp build/gyp || die 'clone gyp'
cd build/gyp || die 'failed cd build/gyp'
git checkout 29e94a3285ee899d14d5e56a6001682620d3778f
rm -rf ./.git || die 'failed to remove .git'
