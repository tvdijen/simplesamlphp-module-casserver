<?php

namespace SimpleSAML\Module\casserver\Cas;


use SimpleSAML_Configuration as Configuration;
use SimpleSAML_Error_BadRequest as BadRequest;
use SimpleSAML\Logger;
use sspmod_casserver_Cas_Ticket_TicketStore as TicketStore;

class TicketValidator
{
    /** @var  Configuration */
    private $casconfig;

    /** @var TicketStore */
    private $ticketStore;

    /**
     * TicketValidator constructor.
     * @param Configuration $casconfig
     */
    public function __construct(Configuration $casconfig)
    {
        $this->casconfig = $casconfig;
        $ticketStoreConfig = $casconfig->getValue('ticketstore', ['class' => 'casserver:FileSystemTicketStore']);
        $ticketStoreClass = \SimpleSAML\Module::resolveClass($ticketStoreConfig['class'], 'Cas_Ticket');
        /** @var TicketStore $ticketStore */
        /** @psalm-suppress InvalidStringClass */
        $this->ticketStore = new $ticketStoreClass($casconfig);
    }


    /**
     * @param string $ticket
     * @param string $service
     */
    public function validateAndDeleteTicket($ticket, $service) {
        if (empty($ticket)) {
            throw new \InvalidArgumentException('Missing ticket parameter: [ticket]');
        }
        if (empty($service)) {
            throw new \InvalidArgumentException('Missing service parameter: [service]');
        }

        $serviceTicket = $this->ticketStore->getTicket($ticket);
        if ($serviceTicket == null) {
            $message = 'Ticket '.var_export($ticket, true).' not recognized';
            Logger::debug('casserver:'.$message);
            return 'INVALID_TICKET';
        }

        // TODO: do proxy vs non proxy ticket check
        $this->ticketStore->deleteTicket($ticket);

        //TODO: check if expired, check if matches service url
        $mismatchMsg = '';
        if (!self::doServiceUrlsMatch($serviceTicket['service'], $service, $mismatchMsg)) {
            Logger::debug('casserver:'. $mismatchMsg);
            throw new BadRequest($mismatchMsg);
        }

        return $serviceTicket;

    }

    public static function doServiceUrlsMatch($ticketUrl, $validateUrl, &$message ='') {
        $sanTicketUrl = self::sanitize($ticketUrl);
        $sanValidateUrl = self::sanitize($validateUrl);
        if ($sanTicketUrl === $sanValidateUrl) {
            return true;
        }
        // determine location of mismatch, since sometimes with encoding issues its hard to tell
        // https://stackoverflow.com/a/7475502/54396
        $position = strspn($sanTicketUrl ^ $sanValidateUrl, "\0");
        $message = 'Mismatching service parameters: expected '.
            var_export($sanTicketUrl, true).
            ' but was: '.var_export($sanValidateUrl, true) .
        " First difference at position $position '{$sanTicketUrl[$position]}' vs '{$sanValidateUrl[$position]}'"
        ;
        return false;
    }

    public static function sanitize($parameter)
    {
        // Make regexes non-greedy incase service url includes more than one ?
        return preg_replace('/;jsessionid=.*[^?].*$/U', '', preg_replace('/;jsessionid=.*[?]/U', '?', urldecode($parameter)));
    }
}