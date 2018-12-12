<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\PostNL\Controller\Adminhtml\Order;

use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use TIG\PostNL\Controller\Adminhtml\LabelAbstract;
use TIG\PostNL\Controller\Adminhtml\PdfDownload as GetPdf;
use TIG\PostNL\Helper\Tracking\Track;
use TIG\PostNL\Service\Converter\CanaryIslandToIC;
use TIG\PostNL\Service\Handler\BarcodeHandler;
use TIG\PostNL\Service\Shipment\CreateShipment;
use TIG\PostNL\Service\Shipment\Labelling\GetLabels;
use TIG\PostNL\Service\Shipment\Packingslip\GetPackingslip;

class CreateShipmentsConfirmAndPrintShippingLabels extends LabelAbstract
{
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var OrderCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CreateShipment
     */
    private $createShipment;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var CanaryIslandToIC
     */
    private $canaryConverter;

    /**
     * @param Context                $context
     * @param GetLabels              $getLabels
     * @param GetPdf                 $getPdf
     * @param Filter                 $filter
     * @param OrderCollectionFactory $collectionFactory
     * @param CreateShipment         $createShipment
     * @param Track                  $track
     * @param BarcodeHandler         $barcodeHandler
     * @param GetPackingslip         $getPackingSlip
     * @param CanaryIslandToIC       $canaryConverter
     */
    public function __construct(
        Context $context,
        GetLabels $getLabels,
        GetPdf $getPdf,
        Filter $filter,
        OrderCollectionFactory $collectionFactory,
        CreateShipment $createShipment,
        Track $track,
        BarcodeHandler $barcodeHandler,
        GetPackingslip $getPackingSlip,
        CanaryIslandToIC $canaryConverter
    ) {
        parent::__construct($context, $getLabels, $getPdf, $getPackingSlip, $barcodeHandler, $track);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->createShipment = $createShipment;
        $this->canaryConverter = $canaryConverter;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->createShipmentsAndLoadLabels();
        $this->handleErrors();

        if (empty($this->labels)) {
            $this->messageManager->addErrorMessage(
                __('[POSTNL-0252] - There are no valid labels generated. Please check the logs for more information')
            );

            return $this->_redirect($this->_redirect->getRefererUrl());
        }

        return $this->getPdf->get($this->labels);
    }

    /**
     * Create or load the shipments and labels
     */
    private function createShipmentsAndLoadLabels()
    {
        $collection = $this->collectionFactory->create();
        $collection = $this->filter->getCollection($collection);

        foreach ($collection as $order) {
            $this->loadLabel($order);
        }
    }

    /**
     * @param Order $order
     */
    private function loadLabel($order)
    {
        if (!in_array($order->getState(), $this->stateToHandel)) {
            $this->messageManager->addWarningMessage(
            //@codingStandardsIgnoreLine
                __('Can not process order %1, because it is not new or in processing', $order->getIncrementId())
            );
            return;
        }

        $shipment = $this->createShipment->create($order);
        if (!$shipment) {
            return;
        }

        $this->canaryConverter->convert($shipment->getShippingAddress());

        $this->barcodeHandler->prepareShipment($shipment->getId(), $shipment->getCountryId());
        $this->setTracks($shipment);
        $this->setLabel($shipment->getId());
    }

    /**
     * @return $this
     */
    private function handleErrors()
    {
        foreach ($this->errors as $error) {
            $this->messageManager->addErrorMessage($error);
        }

        $shipmentErrors = $this->createShipment->getErrors();
        foreach ($shipmentErrors as $error) {
            $this->messageManager->addErrorMessage($error);
        }

        return $this;
    }
}
