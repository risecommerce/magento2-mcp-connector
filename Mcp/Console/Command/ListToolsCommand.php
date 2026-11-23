<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Console\Command;

use Risecommerce\Mcp\Api\ToolRegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `bin/magento risecommerce:mcp:tools:list` — dump every registered MCP tool.
 */
class ListToolsCommand extends Command
{
    public function __construct(
        private readonly ToolRegistryInterface $toolRegistry
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('risecommerce:mcp:tools:list')
            ->setDescription('List all registered MCP tools with their ACL resource and write mode.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tools = $this->toolRegistry->all();
        if ($tools === []) {
            $output->writeln('<comment>No MCP tools registered.</comment>');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Name', 'Title', 'ACL resource', 'Write mode', 'Confirm']);
        foreach ($tools as $tool) {
            $table->addRow([
                $tool->getName(),
                $tool->getTitle(),
                $tool->getAclResource(),
                $tool->getWriteMode()->value,
                $tool->getConfirmationRequired() ? 'yes' : 'no',
            ]);
        }
        $table->render();

        return Command::SUCCESS;
    }
}
