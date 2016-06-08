<?php namespace Phluid\Middleware\ExceptionHandler; ?><!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8">
  <title>Application Error: <?php echo $exception->getMessage() ?></title>
  <style type="text/css" media="screen">
  body {
    margin: 10px 10%;
    font: 14px "Helvetica Neue", "Helvetica-Neue", HelveticaNeue, Helvetica;
    font-weight: 200;
    color: #666;
    min-width: 720px;
    line-height: 1.5;
  }
  h1 {
    font-weight: normal;
    font-size: 21px;
    border-bottom: 1px solid #CCC;
    margin: 0 0 14px;
    padding: 7px 0;
  }
  h2 {
    font-weight: normal;
    font-size: 14px;
    padding: 0;
    margin: 14px 0;
  }
  p {
    font-size: 21px;
    padding: 0;
    margin: 14px 0;
  }
  ol {
    padding: 0;
    margin: 0;
    display: table;
    width: 100%;
  }

  .line {
    color: #222;
    text-align: right;
  }

  .line a {
    color: #666;
  }

  .line a:hover {
    color: #000;
  }

  .trace {
    color: #333;
  }

  .file {
    font-family: monospace;
    font-weight: normal;
    font-size: 12px;
  }

  li {
    display: table-row;
    cursor: default;
  }

  li div {
    display: table-cell;
    padding: 7px;
    border-bottom: 1px solid #F2F2F2;
  }

  li.framework div {
    background: #F9F9F9;
  }

  li:hover div {
    color: #000;
  }

  .trace-toggle {
    display:inline-block;
    margin: 14px 0;
    padding: 1px 7px;
    background: #CCC;
    color: #FFF;
    text-decoration: none;
  }

  .trace-toggle:hover {
    background: #666;
  }

  .trace-toggle:active {
    background: #444;
  }

  </style>
</head>
<body id="500" onload="">
  <h1>Application Error</h1>
  <p><?php echo $exception->getMessage(); ?></p>
  <p><?php //echo common_path( $exception->getFile(), $app_path ) ?> <?php // echo $exception->getLine() ?></p>
</body>
</html>