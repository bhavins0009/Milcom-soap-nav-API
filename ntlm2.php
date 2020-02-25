<?php

define('USERPWD', 'NNH1:N3wN0rdiCHow3');


class NTLMStream
{
    private $path;
    private $mode;
    private $options;
    private $opened_path;
    private $buffer;
    private $pos;

    /**
     * Open the stream
     *
     * @param unknown_type $path
     * @param unknown_type $mode
     * @param unknown_type $options
     * @param unknown_type $opened_path
     * @return unknown
     */
    public function stream_open($path, $mode, $options, $opened_path)
    {
        $this->path = $path;
        $this->mode = $mode;
        $this->options = $options;
        $this->opened_path = $opened_path;
        $this->createBuffer($path);
        return true;
    }

    /**
     * Close the stream
     *
     */
    public function stream_close()
    {
        curl_close($this->ch);
    }

    /**
     * Read the stream
     *
     * @param int $count number of bytes to read
     * @return content from pos to count
     */
    public function stream_read($count)
    {
        if (strlen($this->buffer) == 0) {
            return false;
        }
        $read = substr($this->buffer, $this->pos, $count);
        $this->pos += $count;
        return $read;
    }

    /**
     * write the stream
     *
     * @param int $count number of bytes to read
     * @return content from pos to count
     */
    public function stream_write($data)
    {
        if (strlen($this->buffer) == 0) {
            return false;
        }
        return true;
    }

    /**
     *
     * @return true if eof else false
     */
    public function stream_eof()
    {
        return ($this->pos > strlen($this->buffer));
    }

    /**
     * @return int the position of the current read pointer
     */
    public function stream_tell()
    {
        return $this->pos;
    }

    /**
     * Flush stream data
     */
    public function stream_flush()
    {
        $this->buffer = null;
        $this->pos = null;
    }

    /**
     * Stat the file, return only the size of the buffer
     *
     * @return array stat information
     */
    public function stream_stat()
    {
        $this->createBuffer($this->path);
        $stat = array(
            'size' => strlen($this->buffer),
        );
        return $stat;
    }

    /**
     * Stat the url, return only the size of the buffer
     *
     * @return array stat information
     */
    public function url_stat($path, $flags)
    {
        $this->createBuffer($path);
        $stat = array(
            'size' => strlen($this->buffer),
        );
        return $stat;
    }

    /**
     * Create the buffer by requesting the url through cURL
     *
     * @param unknown_type $path
     */
    private function createBuffer($path)
    {
        if ($this->buffer) {
            return;
        }
        $this->ch = curl_init($path);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($this->ch, CURLOPT_USERPWD, USERPWD);
        $this->buffer = curl_exec($this->ch);
        $this->pos = 0;
    }
}


class NTLMSoapClient extends SoapClient
{
    function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $headers = array(
            'Method: POST',
            'Connection: Keep-Alive',
            'User-Agent: PHP-SOAP-CURL',
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "' . $action . '"',

        );
        $this->__last_request_headers = $headers;
        $ch = curl_init($location);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($ch, CURLOPT_USERPWD, USERPWD);
        $response = curl_exec($ch);

        return $response;
    }

    function __getLastRequestHeaders()
    {
        return implode("\n", $this->__last_request_headers) . "\n";
    }
}

// we unregister the current HTTP wrapper
stream_wrapper_unregister('http');
// we register the new HTTP wrapper
stream_wrapper_register('http', 'NTLMStream') or die("Failed to register protocol");

// Initialize Soap Client
//http://83.91.84.146:7049/DynamicsNAV/WS/7000%20New%20Nordic%20Home/Codeunit/NewNordicHome
$baseURL = 'http://83.91.84.146:7049/DynamicsNAV/WS/7000%20New%20Nordic%20Home/Codeunit/NewNordicHome';
//$baseURL = 'http://83.91.84.146:7049/DynamicsNAV/WS/5980 ScanSport/Codeunit/ScanSport';
$soap = $client = new NTLMSoapClient($baseURL);

// echo '<pre>';
// echo '<h2>Types:</h2>';
// $types = $soap->__getTypes();
// foreach ($types as $type) {
//     $type = preg_replace(
//         array('/(\w+) ([a-zA-Z0-9]+)/', '/\n /'),
//         array('<font color="green">${1}</font> <font color="blue">${2}</font>', "\n\t"),
//         $type
//     );
//     echo $type;
//     echo "\n\n";
// }
// echo '</pre>';

echo '<pre>';
// Get all functions
//var_dump($client->__getFunctions());
//$response = $client->__soapCall('HelloWorld');
// echo '<pre>';
// print_r($response);
// exit;


$ourParamsArray = array('items' => array('item' => ''), 'no' => '');
$response = $client->__soapCall('GetItems', array('parameters' => $ourParamsArray));
echo '<pre>';
print_r($ourParamsArray);
exit;

// Create customer
$header = array("orderNo"=> "4465464",
                "buyFromCustomerNo"=> "4465464",
                "orderDate"=> "02022019",
                "currency"=> "DKK");

$purchOrderLine = array("itemNo"=> "1234",
                "itemName"=> "Test item",
                "quantity"=> "1",
                "total"=> "100");

$purchOrderLineList = array("purchOrderLine" => array($purchOrderLine));
$purchOrder = array("header" => $header, "purchOrderLineList" => $purchOrderLineList);
$ourParamsArray = array('purchOrder' => $purchOrder);

$response = $client->__soapCall('CreatePurchOrder', array('parameters' => $ourParamsArray));
echo '<pre>';
print_r($response);
exit;

// Get Receipt
$ourParamsArray = array('orderNo' => '60802000081', 'result' => '');
$response = $client->__soapCall('GetReceipts', array('parameters' => $ourParamsArray));

// Get GetPurchOrderStatus
$ourParamsArray = array('purchOrderNo' => '60802000081', 'result' => '');
$response = $client->__soapCall('GetPurchOrderStatus', array('parameters' => $ourParamsArray));

// Get shipments
$ourParamsArray = array('orderNo' => '60802000081', 'result' => '');
$response = $client->__soapCall('GetShipments', array('parameters' => $ourParamsArray));

// Get all items
$ourParamsArray = array('items' => '', 'no' => '');
$response = $client->__soapCall('GetItems', array('parameters' => $ourParamsArray));

// Create customer
$ourParamsArray = array('customerNo' => '10', 'customerName' => 'Aadhar Joshi', 
                        'customerAddress1' => 'G-302', 'customerAddress2' => ' Avalon Courtyard',
                        'customerPostalNo' => '380050', 'customerCity' => 'Ahmedabad', 
                        'customerCounty' => 'xyz', 'customerCountry' => 'India', 
                        'customerPhoneNo' => '1234567890', 'result' => 'asdasd');
$response = $client->__soapCall('CreateCustomer', array('parameters' => $ourParamsArray));
echo '<pre>';
print_r($response);

// Get all customers
$ourParamsArray = array('customerNo' => '10', 'customerName' => 'Aadhar Joshi', 
                        'customerAddress1' => 'G-302', 'customerAddress2' => ' Avalon Courtyard',
                        'customerPostalNo' => '380050', 'customerCity' => 'Ahmedabad', 
                        'customerCounty' => 'xyz', 'customerCountry' => 'India', 
                        'customerPhoneNo' => '1234567890', 'result' => 'asdasd');
$response = $client->__soapCall('CreateCustomer', array('parameters' => $ourParamsArray));
exit();

//$client = new NTLMSoapClient($baseURL . '7000NewNordicHome');

// Find the first Company in the Companies

// $result = $client->GetItems(array('string' => "items"));

// if (is_soap_fault($result)) {
//     trigger_error("SOAP Fault: (faultcode: {$result->faultcode}, faultstring: {$result->faultstring})", E_USER_ERROR);
// }
// print_r($result);

stream_wrapper_restore('http');

