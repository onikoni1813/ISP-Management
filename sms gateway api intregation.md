Laravel Package Configurations
Installation
Install the package via Composer:

composer require sms_net_bd/sms
Set your SMS API key in the .env file:

SMS_NET_BD_API_KEY=your-api-key
Usage
use sms_net_bd\SMS;

// Create an instance of the class
$sms = new SMS();

try {
    // Send Single SMS
    $response = $sms->sendSMS(
        "Hello, this is a test SMS!",
        "01701010101"
    );

    // Send Multiple Recipients SMS
    $response = $sms->sendSMS(
        "Hello, this is a test SMS!",
        "01701010101,+8801856666666,8801349494949,01500000000"
    );

    // Send SMS With Sender ID or Masking Name
    $response = $sms->sendSMS(
        "Hello, this is a test SMS!",
        "01701010101",
        "sms.net.bd"
    );

    // Schedule SMS for future delivery
    $response = $sms->sendScheduledSMS(
        "Scheduled SMS",
        "8801701010101",
        "2023-12-01 14:30:00" // Date format: YYYY-MM-DD HH:MM:SS
    );

    // Schedule SMS for future delivery with Sender ID
    $response = $sms->sendScheduledSMS(
        "Scheduled SMS with date",
        "8801701010101",
        "2023-12-01 14:30:00",
        "sms.net.bd"
    );

    // Get SMS delivery report
    $report = $sms->getReport($requestId);

    // Check account balance
    $balanceInfo = $sms->getBalance();

} catch (Exception $e) {
    // handle $e->getMessage();
}
Note: Ensure to replace placeholder values with your actual API key, phone numbers, and messages.
Error Handling
The package provides better error handling. If the API response has an error (error != 0), an exception is thrown with the error message.

try {
    $response = $sms->sendSMS('Invalid Recipient', '+invalid-number');
} catch (\Exception $e) {
    // Handle the exception, log the error, or display a user-friendly message.
    echo 'Error: ' . $e->getMessage();
}




Alpha SMS API Documentation

How to Send SMS Through API
Alpha SMS API is a RESTful API that lets you send SMS messages from your software or application in Bangladesh. You can use both GET and POST methods to make requests to the API endpoint. The API supports JSON format for both requests and responses, making it easy to integrate with any platform. To get started, you need to obtain your API Key from the SMS Panel in "API" page and follow the instructions below.

Find our open-source projects and contribute on GitHub:

 GitHub Profile
Send SMS
Endpoint
https://api.sms.net.bd/sendsms
Parameters
Name	Meaning
api_key	Required. Your API KEY is used to authenticate in our system.
msg	Required. The body content of your message.
to	Required. The recipient numbers. The Number must start with country code(880) or Standard 01X.
Multiple numbers must be separated by comma(,) [applicable for only campaign SMS].
schedule	Optional. The schedule date and time to send your message. Date and time must be formatted as Y-m-d H:i:s (eg. 2021-10-13 16:00:52 )
sender_id	Optional. If you have an approved Sender ID, you can use this parameter to set your Sender ID as from in you messages.
content_id	Optional. (Required for bulk sms) If you have an approved campaign content, you can use this parameter to set the ID of the content to use.
Request
https://api.sms.net.bd/sendsms?api_key={YOUR_API_KEY}&msg={YOUR_MSG}&to=8801800000000,8801700000000&schedule=2021-10-13 16:00:52
Response
{
    "error": 0,
    "msg": "Request successfully submitted",
    "data": {
        "request_id": 0000
    }
}
Report API
Endpoint
https://api.sms.net.bd/report/request/{id}/
Parameters
Name	Meaning
api_key	Required. Your API KEY is used to authenticate in our system.
Request
https://api.sms.net.bd/report/request/{id}/?api_key={YOUR_API_KEY}
Response
{
  "error": 0,
  "msg": "Success",
  "data": {
    "request_id": 0000,
    "request_status": "Complete",
    "request_charge": "0.0000",
    "recipients": [
      {
        "number": "8801800000000",
        "charge": "0.0000",
        "status": "Sent"
      }
    ]
  }
}
Balance API
Endpoint
https://api.sms.net.bd/user/balance/
Parameters
Name	Meaning
api_key	Required. Your API KEY is used to authenticate in our system.
Request
https://api.sms.net.bd/user/balance/?api_key={YOUR_API_KEY}
Response
{
  "error": 0,
  "msg": "Success",
  "data": {
    "balance": "00.0000"
  }
}
Error Codes
Common Errors
Error - 0	Success. Everything worked as expected.
Error - 400	The request was rejected, due to a missing or invalid parameter.
Error - 403	You don't have permissions to perform the request.
Error - 404	The requested resource not found.
Error - 405	Authorization required.
Error - 409	Unknown error occurred on Server end.
Send SMS Errors
Error - 410	Account expired
Error - 411	Reseller Account expired or suspended
Error - 412	Invalid Schedule
Error - 413	Invalid Sender ID
Error - 414	Message is empty
Error - 415	Message is too long
Error - 416	No valid number found
Error - 417	Insufficient balance
Error - 420	Content Blocked