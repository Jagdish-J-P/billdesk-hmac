<?php

namespace JagdishJP\BilldeskHmac\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use JagdishJP\BilldeskHmac\Facades\BilldeskHmac;
use JagdishJP\BilldeskHmac\Messages\TransactionStatus as MessagesTransactionStatus;
use JagdishJP\BilldeskHmac\Models\Transaction;

class TransactionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billdesk:transaction-status {--orderid=* : Order Reference Id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get status of payment.';

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
     * @return int
     */
    public function handle()
    {
        $orderids = $this->option('orderid');

        if (! empty($orderids)) {
            $orderids = Transaction::whereIn('orderid', $orderids)->get('orderid')->toArray();
        }
        else {
            $orderids = Transaction::whereNull('transaction_status')
                ->orWhere('transaction_status', MessagesTransactionStatus::STATUS_PENDING_CODE)
                ->get('orderid')->toArray();
        }

        if ($orderids) {
            try {
                $bar = $this->output->createProgressBar(count($orderids));
                $bar->start();
                foreach ($orderids as $key => $row) {
                    try {
                        $status[] = BilldeskHmac::getTransactionStatus($row['orderid']);
                        logger('Transaction Status: orderid-' . $row['orderid'], $status[$key]);
                    }
                    catch (Exception $e) {
                        $status[$key]['status']               = 'error';
                        $status[$key]['message']              = $e->getMessage();
                        $status[$key]['transaction_response'] = '-';
                        $status[$key]['orderid']              = '-';
                        $status[$key]['transaction_id']       = '-';
                        $status[$key]['transaction_date']     = '-';
                    }
                    $bar->advance();
                }
                $bar->finish();

                $this->newLine();
                $this->newLine();

                $this->table(collect(Arr::first($status))->keys()->toArray(), $this->stringify($status));
                $this->newLine();
                $this->info('Please see log for detailed info.');
            }
            catch (Exception $e) {
                $this->error($e->getMessage());
                logger('Transaction Status', [
                    'message' => $e->getMessage(),
                ]);
            }
        }
        else {
            $this->error('There is no Pending transactions.');
            logger('Transaction Status', [
                'message' => 'There is no Pending transactions.',
            ]);
        }
    }

    protected function stringify($arr)
    {
        $resultArray = [];
        foreach ($arr as $array) {
            foreach ($array as $key => $value) {
                $temp[$key] = (is_array($value) || is_object($value)) ? 'object[]' : $value;
            }
            $resultArray[] = $temp;
        }

        unset($temp);

        return $resultArray;
    }
}
