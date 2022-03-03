<?php
/**
 * @author      FCamara - Formação e Consultoria <contato@fcamara.com.br>
 * @author      Guilherme Miguelete <guilherme.miguelete@fcamara.com.br>
 * @license     Pagaleve Tecnologia Financeira | Copyright
 * @copyright   2022 Pagaleve Tecnologia Financeira (http://www.pagaleve.com.br)
 *
 * @link        http://www.pagaleve.com.br
 */

declare(strict_types=1);

namespace Pagaleve\Payment\Logger;

use Pagaleve\Payment\Helper\Config as HelperConfig;

class Logger extends \Monolog\Logger
{
    /** @var HelperConfig $helperConfig */
    private HelperConfig $helperConfig;

    /**
     * Logger constructor.
     * @param string $name
     * @param HelperConfig $helperConfig
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        string $name,
        HelperConfig $helperConfig,
        array $handlers = array(),
        array $processors = array()
    )
    {
        parent::__construct($name, $handlers, $processors);
        $this->helperConfig = $helperConfig;
    }

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = array())
    {
        if ($this->helperConfig->enabledLog()) {
            return parent::info($message, $context);
        }
    }
}
