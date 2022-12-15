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

namespace Pagaleve\Payment\Controller\Checkout;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Pagaleve\Payment\Helper\Data as HelperData;

class Cancel implements HttpGetActionInterface
{
    /**
     * @var RedirectFactory
     */
    protected RedirectFactory $resultRedirectFactory;

    /**
     * @var HelperData
     */
    protected HelperData $helperData;

    /**
     * Constructor
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param HelperData $helperData
     */
    public function __construct(RedirectFactory $resultRedirectFactory, HelperData $helperData)
    {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helperData = $helperData;
    }

    /**
     * Execute view action
     *
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl('/');
        return $resultRedirect;
    }
}
