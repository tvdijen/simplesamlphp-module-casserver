<?php

namespace SimpleSAML\Module\casserver\Cas;


use SimpleSAML\Error\BadRequest;
use SimpleSAML\Logger;
use SimpleSAML\Module\casserver\Cas\Ticket\TicketStore;

class TicketValidator
{
    /** @var  \SimpleSAML\Configuration */
    private $casconfig;

    /** @var TicketStore */
    private $ticketStore;

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
            $message = 'Ticket '.var_export($_GET['ticket'], true).' not recognized';
            Logger::debug('casserver:'.$message);
            return 'INVALID_TICKET';
        }

        // TODO: do proxy vs non proxy ticket check
        $this->ticketStore->deleteTicket($ticket);

        //TODO: check if expired, check if matches service url
        if (self::sanitize($serviceTicket['service']) !== self::sanitize($_GET['service'])) {
            $message = 'Mismatching service parameters: expected '.
                var_export($serviceTicket['service'], true).
                ' but was: '.var_export($_GET['service'], true);

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