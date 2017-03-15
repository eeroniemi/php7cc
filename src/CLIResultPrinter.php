<?php

namespace Sstalle\php7cc;

use PhpParser\PrettyPrinter\Standard as StandardPrettyPrinter;
use Sstalle\php7cc\CompatibilityViolation\CheckMetadata;
use Sstalle\php7cc\CompatibilityViolation\ContextInterface;
use Sstalle\php7cc\CompatibilityViolation\Message;

class CLIResultPrinter implements ResultPrinterInterface
{
    /**
     * @var CLIOutputInterface
     */
    protected $output;

    /**
     * @var StandardPrettyPrinter
     */
    protected $prettyPrinter;

    protected $fieldSeparator = "|";

    protected $codeMaxLength = 20;

    /**
     * @var NodeStatementsRemover
     */
    protected $nodeStatementsRemover;

    private static $levelLabels = [
        Message::LEVEL_INFO => 'info',
        Message::LEVEL_WARNING => 'warning',
        Message::LEVEL_ERROR => 'error',
	];

    /**
     * @param CLIOutputInterface    $output
     * @param StandardPrettyPrinter $prettyPrinter
     * @param NodeStatementsRemover $nodeStatementsRemover
     */
    public function __construct(
        CLIOutputInterface $output,
        StandardPrettyPrinter $prettyPrinter,
        NodeStatementsRemover $nodeStatementsRemover
    ) {
        $this->output = $output;
        $this->prettyPrinter = $prettyPrinter;
        $this->nodeStatementsRemover = $nodeStatementsRemover;

        $this->output->writeln("File" . $this->fieldSeparator . "Line" . $this->fieldSeparator . "Level" . $this->fieldSeparator . "Message" . $this->fieldSeparator . "Code");
    }

    /**
     * {@inheritdoc}
     */
    public function printContext(ContextInterface $context)
    {
        $file = sprintf('%s', $context->getCheckedResourceName());
        
        foreach ($context->getMessages() as $message) {
            $this->output->writeln(
                $file . $this->fieldSeparator . $this->formatMessage($message)
            );
        }

        foreach ($context->getErrors() as $error) {
            $this->output->writeln(
                $file . $this->fieldSeparator . $this->fieldSeparator . $this->fieldSeparator . $error->getText()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function printMetadata(CheckMetadata $metadata)
    {
    }

    /**
     * @param Message $message
     *
     * @return string
     */
    private function formatMessage(Message $message)
    {
        $nodes = $this->nodeStatementsRemover->removeInnerStatements($message->getNodes());
        $prettyPrintedNodes = str_replace("\n", "\n    ", $this->prettyPrinter->prettyPrint($nodes));

        $text = $message->getRawText();

        return sprintf(
            "%s" . $this->fieldSeparator . "%s" . $this->fieldSeparator . "%s" . $this->fieldSeparator . "%s",
            $message->getLine(),
            self::$levelLabels[$message->getLevel()],
            $text,
            substr(str_replace(["\n", $this->fieldSeparator], "", $prettyPrintedNodes), 0, $this->codeMaxLength)
        );
    }
}
