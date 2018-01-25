<?php
/**
 * This file is part of the nepada/bust-cache.
 * Copyright (c) 2016 Petr Morávek (petr@pada.cz)
 */

declare(strict_types = 1);

namespace Nepada\BustCache;

use Latte;
use Latte\MacroNode;


/**
 * Macro {bustCache ...}
 */
class BustCacheMacro implements Latte\IMacro
{

    use Latte\Strict;

    /** @var string */
    private $wwwDir;

    /** @var bool */
    private $debugMode;


    /**
     * @param string $wwwDir
     * @param bool $debugMode
     */
    public function __construct(string $wwwDir, bool $debugMode = false)
    {
        $this->wwwDir = $wwwDir;
        if (!is_dir($this->wwwDir)) {
            throw DirectoryNotFoundException::fromDir($wwwDir);
        }
        $this->debugMode = $debugMode;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
     */
    public function initialize()
    {
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
     */
    public function finalize()
    {
    }

    /**
     * New node is found. Returns FALSE to reject.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
     *
     * @param MacroNode $node
     * @return bool
     * @throws Latte\CompileException
     */
    public function nodeOpened(MacroNode $node)
    {
        if ($node->prefix != '') { // intentionally !=
            return false;
        }

        if ($node->modifiers != '') {  // intentionally !=
            throw new Latte\CompileException("Modifiers are not allowed in {{$node->name}}.");
        }

        /** @var string|false $file */
        $file = $node->tokenizer->fetchWord();
        if ($file === false) {
            throw new Latte\CompileException("Missing file name in {{$node->name}}.");
        }

        /** @var string|false $word */
        $word = $node->tokenizer->fetchWord();
        if ($word !== false) {
            throw new Latte\CompileException("Multiple arguments are not supported in {{$node->name}}.");
        }

        $node->isEmpty = true;
        $node->modifiers = '|safeurl|escape'; // auto-escape

        $writer = Latte\PhpWriter::using($node);

        if ($this->debugMode) {
            $node->openingCode = $writer->write('<?php echo %modify(%1.word . \'?\' . Nepada\BustCache\Helpers::timestamp(%0.var . %1.word)) ?>', $this->wwwDir, $file);

        } elseif (preg_match('#^(["\']?)[^$\'"]*\1$#', $file)) { // Static path
            $file = trim($file, '"\'');
            $url = $file . '?' . Helpers::hash($this->wwwDir . $file);
            $url = Latte\Runtime\Filters::safeUrl($url);
            $node->openingCode = $writer->write('<?php echo %escape(%var) ?>', $url);

        } else {
            $node->openingCode = $writer->write('<?php echo %modify(%1.word . \'?\' . Nepada\BustCache\Helpers::hash(%0.var . %1.word)) ?>', $this->wwwDir, $file);
        }

        return true;
    }

    /**
     * Node is closed.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
     *
     * @param MacroNode $node
     */
    public function nodeClosed(MacroNode $node)
    {
    }

}
