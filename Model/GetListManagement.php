<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 *
 * This product includes proprietary software developed at BroSolutions, Ukraine
 * For more information see https://www.brosolutions.net/
 *
 * To obtain a valid license for using this software please contact us at
 * contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Model;

use BroSolutions\QuickOrder\Service\GetProductsData;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GetListManagement
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var GetProductsData
     */
    private $getProductsData;

    /**
     * @param Json $json
     * @param Filesystem $filesystem
     * @param GetProductsData $getProductsData
     */
    public function __construct(
        Json $json,
        Filesystem            $filesystem,
        GetProductsData $getProductsData
    ) {
        $this->json = $json;
        $this->filesystem = $filesystem;
        $this->getProductsData = $getProductsData;
    }

    /**
     * Create a list of products
     *
     * @param string $params
     * @return array
     * @throws FileSystemException
     */
    public function getList(string $params) : array
    {
        $params = $this->json->unserialize($params);
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        $filepath = 'export/quick_order_product_list.csv';
        $directory->create('export');
        $stream = $directory->openFile($filepath, 'w+');
        $stream->lock();

        $productsData = $this->getProductsData->execute($params);

        $header = ['sku','qty'];
        $i = 0;
        while ($i < $productsData['total']) {
            $header[] = 'option_' . ($i + 1);
            $i++;
        }

        $stream->writeCsv($header);
        foreach ($productsData['productData'] as $productsDataRow) {
            $stream->writeCsv($productsDataRow);
        }

        $stream->unlock();
        $stream->close();

        $content = [];
        $content['type'] = 'filename';
        $content['value'] = $filepath;
        $content['rm'] = '1';

        $csvFileName = 'quick_order_product_list_' . date('m_d_Y_H_i') . '.csv';

        return ['csvFileName' => $csvFileName, 'content' => $content, 'baseDir' => DirectoryList::VAR_DIR];
    }
}
