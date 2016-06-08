<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="UTF-8">
  <title>[cjdns]</title>

  <link rel="stylesheet" href="bootstrap.min.css">

  <style>

    body {
      margin: 14px;
      font: 8px Helvetica, sans-serif;
      font-weight: 100;
      vertical-align: all;
    }

    th {
      font: 11px Helvetica, sans-serif;
      background-color: green;
      color: white;
    }

    tr:hover {
        background-color: #f5f5f5
    }

    tr:nth-child(even) {
        background-color: #f2f2f2
    }

    td {
      font: 11px Helvetica, sans-serif;
      vertical-align: left;
    }

    pre {
      background-color: black;
      color: green;
      display: block;
      font-family: monospace;
      white-space: pre;
      margin: 2em 0;
    }

  </style>


  <link rel="stylesheet" href="bootstrap-table.min.css">

  <script src="jquery-2.0.2.min.js"></script>
  <script src="bootstrap.min.js"></script>
  <script src="bootstrap-table.min.js"></script>

  <script type="text/javascript">

    $(window).resize(function() {
        $('#peerstats-table').bootstrapTable('resetView');
    });

    jQuery(document).ready(function($) {

      setInterval(function() {

        jQuery.ajax({
          url: '/nodes',
          type: 'GET',
          dataType: 'json',
          data: {q: 'nodes'},
          success: function(xhr, textStatus)
          {

            $('#peerstats-table').bootstrapTable('destroy');
            $('#peerstats-table').bootstrapTable({
                data: xhr.peers
            });

          },
          error: function(xhr, textStatus, errorThrown)
          {
            console.log(errorThrown);
            console.log(xhr);
          }
        });

      }, 1 * 1000);

    });


  </script>

</head>

<body>

  <?php echo '<center><pre>' . $obj->Toolshed->ascii_logo() . '</pre></center><hr>'; ?>

  <center>

  <table id="peerstats-table"
    data-pagination="false"
    data-search="false"
    data-classes="table"
    class="table table-hover table-condensed"
    data-show-columns="false"
    data-show-refresh="false"
    data-show-toggle="false"
    data-striped="false">

    <thead class="smallfont">
        <tr>
            <th data-field="state"     data-sortable="true" class="col-md-0">
                Connectivity
            </th>
            <th data-field="ipv6"      data-sortable="true" class="col-md-0">
                cjdns Address
            </th>
            <th data-field="bytesIn"   data-sortable="true" class="col-md-0">
                Total RX
            </th>
            <th data-field="bytesOut"  data-sortable="true" class="col-md-0">
                Total TX
            </th>
            <th data-field="recvKbps"  data-sortable="true" class="col-md-0">
                RX Speed
            </th>
            <th data-field="sendKbps"  data-sortable="true" class="col-md-0">
                TX Speed
            </th>
            <th data-field="last"      data-sortable="true" class="col-md-0">
                Last Pkt
            </th>
            <th data-field="publicKey" data-sortable="true" class="col-md-0">
                Public Key
            </th>

        </tr>
    </thead>
  </table>

  </center>

  <hr>

</body>