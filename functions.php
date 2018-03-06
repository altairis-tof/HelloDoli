<?php

function updatelog($str)
{
  if(PHP_OS == 'WINNT')
    $logFilePath = 'adhesions.log';
  else
    $logFilePath = '/var/log/hellodoli/adhesions.log';
  if(is_string($str))
  {
    $logFile = fopen($logFilePath, 'a');
    fputs($logFile, $str.PHP_EOL);
    fclose($logFile);
  }
}

 ?>
