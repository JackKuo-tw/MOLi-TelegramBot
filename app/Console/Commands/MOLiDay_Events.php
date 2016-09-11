<?php

namespace MOLiBot\Console\Commands;

use Illuminate\Console\Command;

use Telegram;
use Storage;

class MOLiDay_Events extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kktix:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check New MOLiDay Event From KKTIX';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://moli.rocks/kktix/events.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $fileContents = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($fileContents);
        $events = $json->entry;

        if (Storage::disk('local')->has('KKTIX_published')) {
            $content = Storage::disk('local')->get('KKTIX_published');
        } else {
            Storage::disk('local')->put('KKTIX_published', '[""]');
            $content = Storage::disk('local')->get('KKTIX_published');
        }

        $published = json_decode($content);
        $publishedArray = array();
        $getChanged = 'N';

        foreach ($events as $event) {
            $publishedArray[] = $event->url;
            foreach ($published as $publishedurl) {
                if ($event->url == $publishedurl) {
                    $new = 'N';
                    break;
                } else {
                    $new = 'Y';
                }
            }
            if ($new == 'Y') {
                $getChanged = 'Y';
                //Telegram::sendMessage([
                //    'chat_id' => env('NEWS_CHANNEL'),
                //    'text' => $item['title'] . PHP_EOL . 'http://www.ncnu.edu.tw/ncnuweb/ann/' . $item['link'] . PHP_EOL . PHP_EOL . $hashtag
                //]);
                $this->info($event->title);
                $this->info($event->summary);
                $this->info($event->content);
                $this->info($event->url);
                $this->info('');
                $this->info('');
                sleep(5);
            }
        }

        if ($getChanged == 'Y') {
            Storage::disk('local')->delete('KKTIX_published');
            sleep(1);
            Storage::disk('local')->put('KKTIX_published', json_encode($publishedArray));
        }

        $this->info('Mission Complete!');
    }
}