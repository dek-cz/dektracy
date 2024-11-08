<?php

namespace DekApps\DekTracy\DI;

use Nette\DI\CompilerExtension;
use DekApps\DekTracy\MssqlPanel;
use DekApps\DekTracy\MssqlAdapter;

use Nette\DI\Statement;

class TracyMssqlExtension extends CompilerExtension
{

    public function loadConfiguration(): void
    {
    }

    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('mssqlbaradapter'))->setClass(MssqlAdapter::class);
        $builder->getDefinition('tracy.bar')
                ->addSetup('addPanel', [
                    new Statement(
                            MssqlPanel::class,
                            [$builder->getDefinitionByType(MssqlAdapter::class)]
                    ),
                    $this->prefix('mssqlBar'),
        ]);
    }

}
