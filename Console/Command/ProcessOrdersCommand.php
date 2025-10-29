<?php
namespace Lengow\Connector\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Lengow\Connector\Model\Import as LengowImport;

/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @subpackage  Console
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProcessOrdersCommand extends Command
{
    const INPUT_DAYS = 'days';
    const INPUT_ORDER_IDS = 'marketplace-order-ids';
    const INPUT_MARKETPLACE_NAME = 'marketplace-name';
    const INPUT_STORE_ID = 'store-id';
    const CMD_NAME = 'lengow:orders:process';
    const MEMORY_LIMIT = '1024M';

    /**
     * @var LengowImport $lengowImport
     */
    private LengowImport $lengowImport;

    /**
     * Constructor
     */
    public function __construct(
        LengowImport $lengowImport,
        string $name = null
    ) {

        $this->lengowImport = $lengowImport;
        parent::__construct(self::CMD_NAME);
    }

    /**
     * Configure command options and description
     */
    protected function configure()
    {
        $this->setName(self::CMD_NAME)
            ->setDescription('Process marketplace orders with options')
            ->addOption(
                self::INPUT_DAYS,
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of days to filter orders --days=5 to process orders from the last 5 days'
            )->addOption(
                self::INPUT_ORDER_IDS,
                null,
                InputOption::VALUE_OPTIONAL,
                'Comma-separated marketplace order IDs 123456,654321 for marketplace a specific marketplace and store'
            )->addOption(
                self::INPUT_MARKETPLACE_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Marketplace name (slug) example: amazon_fr, amazon_es, leroymerlin, manomano_fr ... required when using --marketplace-order-ids'
            )->addOption(
                self::INPUT_STORE_ID,
                null,
                InputOption::VALUE_OPTIONAL,
                'Store ID to process orders for a specific store example --store-id=1 --days=5'
            );

        parent::configure();
    }

    /**
     * Execute command to process orders
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', self::MEMORY_LIMIT);
        $days = (float) $input->getOption(self::INPUT_DAYS);
        $orderIdsInput = (string) $input->getOption(self::INPUT_ORDER_IDS);
        $storeId = (int) $input->getOption(self::INPUT_STORE_ID);
        $marketplaceName = (string) $input->getOption(self::INPUT_MARKETPLACE_NAME);

        if (empty($orderIdsInput) && empty($days)) {
            $output->writeln('<error>Please provide at least one parameter: --days or --marketplace-order-ids.</error>');

            return Command::INVALID;
        }

        if ($days < 0) {
            $output->writeln('<error>Please provide a valid --days parameter (float > 0).</error>');

            return Command::FAILURE;
        }

        if ($days) {
            $maxDays = LengowImport::MAX_INTERVAL_TIME / (24 * 60 * 60);
            if ($days > (int) $maxDays) {
                $output->writeln("<error>The maximum allowed days is $maxDays. Set to $maxDays.</error>");
                $days = $maxDays;
                return Command::INVALID;
            }
            $output->writeln("<info>Configured to process orders from the last $days days.</info>");
            $this->lengowImport->init(
                [
                    LengowImport::PARAM_TYPE => LengowImport::TYPE_MAGENTO_CLI,
                    LengowImport::PARAM_DAYS => $days,
                    LengowImport::PARAM_FORCE_SYNC => true
                ]
            );
            if ($storeId) {
                $output->writeln("<info>Store ID: " . $storeId . "</info>");
                $this->lengowImport->setStoreId($storeId);
            }
            $this->lengowImport->exec();
            $output->writeln('<info>LengowImport executed</info>');

            return Command::SUCCESS;

        }

        $orderIds = [];
        if ($orderIdsInput) {
            $orderIds = array_map('trim', explode(',', $orderIdsInput));
        }
        if (!empty($orderIds)) {
            $output->writeln("<info>Marketplace Order IDs: " . implode(', ', $orderIds) . "</info>");
            if (empty($marketplaceName)) {
                $output->writeln('<error>Please provide the --marketplace-name parameter when using --marketplace-order-ids.</error>');

                return Command::INVALID;
            }
            $output->writeln("<info>Marketplace Name: " . $marketplaceName . "</info>");

            if (empty($storeId)) {
                $output->writeln('<error>Please provide the --store-id parameter when using --marketplace-order-ids.</error>');

                return Command::INVALID;
            }
            $output->writeln("<info>Store ID: " . $storeId . "</info>");


            foreach ($orderIds as $orderId) {
                $this->lengowImport->init(
                    [
                        LengowImport::PARAM_TYPE => LengowImport::TYPE_MAGENTO_CLI,
                        LengowImport::PARAM_MARKETPLACE_SKU => $orderId,
                        LengowImport::PARAM_MARKETPLACE_NAME => $marketplaceName,
                        LengowImport::PARAM_STORE_ID => $storeId,
                        LengowImport::PARAM_FORCE_SYNC => true
                    ]
                );
                $this->lengowImport->setImportOneOrder(true)->setLimit(1);
                $this->lengowImport->exec();
            }
        }
        $output->writeln('<info>LengowImport executed</info>');

        return Command::SUCCESS;
    }
}
