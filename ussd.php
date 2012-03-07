#!/usr/bin/php
<?php

define("CR", "\r");
define("TERMINAL_DEVICE", "/dev/ttyUSB0");
define("ADDITIONAL_DEVICE", "/dev/ttyUSB2");

$args = $_SERVER['argc'];

if($args < 2)
  help();
else
{
  $command = $_SERVER['argv'][1];
  
  if(preg_match("/^\\*[0-9*]+#$/", $command))
  {
      runUSSD($command);
  }
  elseif(preg_match("/^AT.*$/", $command))
  {
      runAT($command);
  }
  else
      error("Incorrect format of command.");
}

exit;

function help()
{
  echo "AT<command>     Send AT command".PHP_EOL;
  echo "*<command>#     Send USSD request".PHP_EOL;
}

function error($message)
{
  echo "Error: ".$message.PHP_EOL;
}

function runAT($command)
{
  echo "AT command: ".$command.PHP_EOL;
  $answer = sendTerminalCommand($command);
  echo "Answer: ".$answer.PHP_EOL;
}

function runUSSD($command)
{
  echo "USSD request: ".$command.PHP_EOL;
  
  $pdu = str2pdu($command);
  $command = "AT+CUSD=1,{$pdu},15";
  runAT($command);
  
  $fp = fopen(ADDITIONAL_DEVICE, 'r');
  $answer = "";
  $ending="\n";
  $starting = "+CUSD:";
  do
  {
    $char = fgetc($fp);
    if(strlen($starting) == 0 && $ending[0] == $char)
    {
      $ending = substr($ending, 1);
    }
    elseif(strlen($starting) && $starting[0] == $char)
    {
      $starting = substr($starting, 1);
    }
    $answer .= $char;
  }
  while(strlen($ending));
  fclose($fp);
  
  $lines = preg_split("/\r\n|\r|\n/", $answer);
  foreach($lines as $answer)
  {
    if(preg_match('/\+CUSD: 0,"(.*)",1/', $answer, $m))
    {
      $pduanswer = $m[1];
      $answer = pdu2str($pduanswer);
      echo "Answer: ".$answer.PHP_EOL; 
    }
  }
}

function str2pdu($command)
{
  $bin = "";
  for($i = 0; $i < strlen($command); $i++)
    $bin .= strrev(sprintf("%07b", ord($command[$i])));
    
  $bin .= str_repeat("0", 8 - strlen($bin) % 8);
  $pdu = "";
  while(strlen($bin))
  {
    $symbol = substr($bin, 0, 8);
    $symbol = strrev($symbol);
    $bin = substr($bin, 8);
    $pdu .= binhex(substr($symbol,0,4)).binhex(substr($symbol,4));
  }

  return $pdu;
}

function pdu2str($pduanswer)
{
  $pdu = pack("H*", $pduanswer);
  $bin = "";
  for($i = 0; $i < strlen($pdu); $i++)
    $bin .= strrev(sprintf("%08b", ord($pdu[$i])));

  $hex = "";
  while(strlen($bin)>=7)
  {
    $symbol = substr($bin, 0, 7);
    $bin = substr($bin, 7);
    
    $symbol = "0".strrev($symbol);    
    $hex .= binhex(substr($symbol,0,4)).binhex(substr($symbol,4));
  }

  return pack("H*", $hex);
}

function binhex($string)
{
  return strtoupper(dechex(bindec($string)));
}

function sendTerminalCommand($command)
{
  $fp = fopen(TERMINAL_DEVICE, 'r+');
  fwrite($fp, $command.CR);
  
  // Read command
  do
  {
      $echo = getString($fp);
  }
  while($echo != $command);
  
  // Read answer
  $answer = getString($fp);
  
  fclose($fp);
  
  return $command ? $answer : NULL;
}

function getString($fp)
{
  $string = "";
  $ending="\n";
  
  do
  {
    $char = fgetc($fp);
    if($ending[0] == $char)
      $ending = substr($ending, 1);  
    if($char != "\r" && $char != "\n")
      $string .= $char;
  }
  while(strlen($ending));
  
  return $string;
}