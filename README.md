USSD tool
=========

This tool allows to send AT and USSD request to 3G modem.

Supported hardware:
* Huawei E1550

Requirements:
* linux
* php-cli binary

Setup:  
It should work by default. But you may try to change:
* Path to php-cli binary in the first line of file
* Constant TERMINAL_DEVICE
* Constant ADDITIONAL_DEVICE

Usage:  
  AT\<command\>     Send AT command  
  *\<command\>#     Send USSD request  
