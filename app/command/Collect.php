<?php

namespace app\command;

use QL\QueryList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;


class Collect extends Command
{
    protected static $defaultName = 'collect';
    protected static $defaultDescription = 'collect';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
//        $name = $input->getArgument('name');
        $data = QueryList::get('https://wordsofwonders.net/en/')
            ->rules([
                'one_level_name'=>array('','text'),
                'link'=>array('','href')
            ])->range(".levels a")
            ->query()
            ->getData()
            ->all();

        foreach ($data as &$item) {
            $output->writeln("start level page:".$item['one_level_name']);
            $item['levels'] = QueryList::get('https://wordsofwonders.net'.$item['link'])
//                QueryList::get('https://wordsofwonders.net/en/yellowstone-national-park/')
                ->rules([
                    'two_level_name'=>array('a','text'),
                    'link'=>array('a','href')
                ])->range(".levels .lvl")
                ->query()
                ->getData()
                ->all();

            foreach ($item['levels'] as &$value) {
                $output->writeln("start level page:".$value['two_level_name']);

                try {
                    //                $ql = QueryList::get('https://wordsofwonders.net/en/yellowstone-national-park/level-1003');
                    $ql = QueryList::get('https://wordsofwonders.net'.$value['link']);

                    $value['content'] = $ql->find(".crossword .crossword-row")->map(function ($item) {
                        return $item->find(".letter")->texts()->all();
                    })->all();

                    $content2HtmlArray = explode("<br>", $ql->find(".words")->html());

                    $content2Array = [];
                    foreach ($content2HtmlArray as $str) {
                        $content2 = QueryList::html($str)->find(".let")->texts()->all();
                        if (!count($content2)) continue;
                        $content2Array[] = $content2;
                    }
//                var_dump($content2Array);
//                return self::SUCCESS;

                    $value['content2'] = $content2Array;
                }catch (\Throwable $throwable) {
                    continue;
                }

            }
        }

        file_put_contents("levels.json", json_encode($data));
        $output->writeln('finish');
        return self::SUCCESS;
    }

}
