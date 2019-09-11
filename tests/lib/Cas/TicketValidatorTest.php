<?php

use SimpleSAML\Module\casserver\Cas\TicketValidator;

/**
 * Created by PhpStorm.
 * User: patrick
 * Date: 9/6/19
 * Time: 8:41 AM
 */

class TicketValidatorTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @dataProvider serviceUrlMatchProvider
     * @param string $ticketUrl
     * @param string $validateUrl
     * @param bool $match
     */
    public function testServiceUrlMatch(string $ticketUrl, string $validateUrl, bool $match) {
        $msg = '';
        $result = TicketValidator::doServiceUrlsMatch($ticketUrl, $validateUrl, $msg);
        $this->assertEquals($match, $result, $msg);
        $this->assertEquals($match, empty($msg));
    }

    public function serviceUrlMatchProvider() {
        return [
            [
                'https://example.edu/kc/portal.do?a=b',
                'https://example.edu/kc/portal.do;jsessionid=99AC064A12?a=b',
                true,
            ],
            [
                'https://example.edu/kc/portal.do;jsessionid=99AC064A12?a=b',
                'https://example.edu/kc/portal.do?a=b',
                true,
            ],
            [
                'https://example.edu/kc/portal.do?a=b',
                'https://wrong.edu/kc/portal.do?a=b',
                false,
            ],
          [
            'https://kualtest.wvu.edu/kc/portal.do?channelTitle=Search&channelUrl=https://kualtest.wvu.edu/kc/ajaxSearchIRB.do?simpleSearchField=123456789*&runSearchOnLoad=1',
              'https://kualtest.wvu.edu/kc/portal.do;jsessionid=99AC064A12760FD4B47A5D6A1D694FAF?channelTitle=Search&channelUrl=https://kualtest.wvu.edu/kc/ajaxSearchIRB.do?simpleSearchField=123456789*&runSearchOnLoad=1',
              true,
          ]
        ];
    }
}