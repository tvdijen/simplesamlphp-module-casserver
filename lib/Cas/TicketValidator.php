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
        if (self::sanitize($serviceTicket['service']) !== self::sanitize($service)) {
            $message = 'Mismatching service parameters: expected '.
                var_export($serviceTicket['service'], true).
                ' but was: '.var_export($service, true);

            Logger::debug('casserver:'.$message);
            throw new BadRequest($message);
        }

        return $serviceTicket;

    }

    public static function sanitize($parameter)
    {
        return preg_replace('/;jsessionid=.*[^?].*$/', '', preg_replace('/;jsessionid=.*[?]/', '?', urldecode($parameter)));
    }
}