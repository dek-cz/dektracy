<?php

declare(strict_types=1);

namespace DekApps\DekTracy;

use Tracy\IBarPanel;
use Latte;
use Latte\Runtime\Filters;
use Nette\Utils\Strings;

class MssqlPanel implements IBarPanel
{

    /**
     *
     * @var MssqlAdapter 
     */
    private $adapter;

    public function __construct(MssqlAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return string
     */
    public function getTab()
    {
//        $img = Filters::dataStream(file_get_contents(__DIR__ . '/templates/database.png'));
        $img = base64_encode(file_get_contents(__DIR__ . '/templates/database.png'));
        $html = '<span title="DekApps\MssqlProcedure"><img src="data:image/png;base64,' . $img . '" />' . $this->adapter->getCountConn() . ' queries / ' . round($this->adapter->getTime(), 1) . ' ms</span>';
        return $html;
    }

    /**
     * @return string
     */
    public function getPanel()
    {
        $dumps = $this->adapter->getDump();
        foreach ($dumps as &$conn) {
            if (!preg_match('/^\[[a-zA-Z0-9_]+\]\./', $conn['NAME'])) {
                $conn['NAME'] = preg_replace('/^([a-zA-Z0-9_]+)\./', '[$1].', $conn['NAME']);
            }

            foreach ($conn['IN'] as $key => &$in)
            {
                $in['php_type']  = gettype($in['var']);
                if (is_string($in['var'])) {
                    $in['var'] = 'N\'' . $in['var'] . '\'';
                } elseif ($in['var'] instanceof \DateTimeInterface) {
                    $in['var'] = $in['var']->format(\DateTimeInterface::ISO8601);
                } elseif (is_null($in['var'])) {
                    $in['var'] = 'NULL';
                }

                if($in['type'] === SQLSRV_PARAM_OUT) {
                    $conn['OUT'][$in['var_name']] = [
                        'value' => $conn['OUT'][$in['var_name']],
                        'param_name' => $in['param_name'],
                        'var_name' => $in['var_name'],
                        'var_type' => $in['var'],
                    ];
                }
            }

            $output = [];
            foreach ($conn['OUT'] as $key => &$out) {
                if (is_string($out)) {
                    $out = htmlspecialchars($out);
                }
    
                $output[] = $key . ': <em>(' . gettype($out['value']) . ')</em> ' . $out['value'];
            }
//           
        }
//         $dumps['output'] = $output;
//                    dumpe($dumps);
        $this->adapter->setDump($dumps);

        $params = [
            'count' => $this->adapter->getCountConn(),
            'time' => $this->adapter->getTime(),
            'connections' => $this->adapter->getDump()
        ];
        /**
         * @todo: proc na takovou hovadinu Latte ? .... je to pomale
         */
        $latte = new Latte\Engine;
        $latte->addFilter('padRight', function($s, $l, $p = ' ')
        {
            return Strings::padRight($s, $l, $p);
        });
        return $latte->renderToString(__DIR__ . '/templates/panel.latte', $params);
    }

}
