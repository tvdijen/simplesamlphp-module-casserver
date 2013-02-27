<?php
/*
* Incomming parameters:
*  service
*  renew
*  ticket
*
*/

require_once 'utility/urlUtils.php';

/* Load simpleSAMLphp, configuration and metadata */
$casconfig = SimpleSAML_Configuration::getConfig('module_sbcasserver.php');

/* Instantiate protocol handler */
$protocolClass = SimpleSAML_Module::resolveClass('sbcasserver:Cas10', 'Cas_Protocol');
$protocol = new $protocolClass($casconfig);

if (array_key_exists('service', $_GET) && array_key_exists('ticket', $_GET)) {
    $ticketId = sanitize($_GET['ticket']);
    $service = sanitize($_GET['service']);

    $forceAuthn = isset($_GET['renew']) && sanitize($_GET['renew']);

    try {
        /* Instantiate ticket store */
        $ticketStoreConfig = $casconfig->getValue('ticketstore', array('class' => 'sbcasserver:FileSystemTicketStore'));
        $ticketStoreClass = SimpleSAML_Module::resolveClass($ticketStoreConfig['class'], 'Cas_Ticket');
        $ticketStore = new $ticketStoreClass($casconfig);

        $serviceTicket = $ticketStore->getTicket($ticketId);

        if (!is_null($serviceTicket)) {
            $ticketFactoryClass = SimpleSAML_Module::resolveClass('sbcasserver:TicketFactory', 'Cas_Ticket');
            $ticketFactory = new $ticketFactoryClass($casconfig);

            $valid = $ticketFactory->validateServiceTicket($serviceTicket);

            $ticketStore->deleteTicket($ticketId);

            $usernameField = $casconfig->getValue('attrname', 'eduPersonPrincipalName');

            if ($valid['valid'] && $serviceTicket['service'] == $service && (!$forceAuthn || $serviceTicket['forceAuthn']) &&
                array_key_exists($usernameField, $serviceTicket['attributes'])
            ) {
                echo $protocol->getSuccessResponse($serviceTicket['attributes'][$usernameField][0]);
            } else if (!array_key_exists($usernameField, $serviceTicket['attributes'])) {
                SimpleSAML_Logger::debug('sbcasserver:validate: internal server error. Missing user name attribute: ' .
                    var_export($usernameField, TRUE));

                echo $protocol->getFailureResponse();
            } else {
                echo $protocol->getFailureResponse();
            }
        } else {
            echo $protocol->getFailureResponse();
        }
    } catch (Exception $e) {
        SimpleSAML_Logger::debug('sbcasserver:validate: internal server error. ' . var_export($e->getMessage(), TRUE));

        echo $protocol->getFailureResponse();
    }
} else {
    echo $protocol->getFailureResponse();
}
?>
