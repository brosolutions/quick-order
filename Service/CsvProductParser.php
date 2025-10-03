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

namespace BroSolutions\QuickOrder\Service;

use Psr\Log\LoggerInterface;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class CsvProductParser
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Parse CSV file into structured array
     *
     * @param string $filePath
     * @return array
     * @throws \RuntimeException
     */
    public function execute(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        $header = fgetcsv($handle);
        if (!$header) {
            throw new \RuntimeException("CSV file is empty or invalid: {$filePath}");
        }

        $result = [];
        $line   = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if (count($row) < count($header)) {
                $this->logger->warning("Line {$line}: less columns than header. Padding with nulls.", [
                    'row' => $row
                ]);
                $row = array_pad($row, count($header), null);
            } elseif (count($row) > count($header)) {
                $this->logger->warning("Line {$line}: more columns than header. Extra values will be dropped.", [
                    'row' => $row
                ]);
                $row = array_slice($row, 0, count($header));
            }

            $item = array_combine($header, $row);

            if (!$item || empty($item['sku'])) {
                continue; // ignore empty lines
            }

            $options = [];
            foreach ($item as $key => $value) {
                $qty = 1;
                if (str_starts_with($key, 'option_') && !empty($value)) {
                    $parts = explode(':', $value);

                    if (count($parts) === 3) {
                        [$label, $val, $qty] = $parts;
                        $options[] = [
                            'label' => trim($label),
                            'value' => trim($val),
                            'qty'   => (int)$qty,
                        ];
                    } elseif (count($parts) === 2) {
                        [$label, $val] = $parts;
                        $options[] = [
                            'label' => trim($label),
                            'value' => trim($val),
                        ];
                    }
                }

            }

            $result[] = [
                'sku'     => trim($item['sku']),
                'qty'     => (int)$item['qty'],
                'options' => $options
            ];
        }

        fclose($handle);

        return $result;
    }
}
