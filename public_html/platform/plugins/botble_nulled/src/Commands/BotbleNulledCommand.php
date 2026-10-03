<?php

namespace Botble\BotbleNulled\Commands;

use Botble\Base\Commands\Traits\ValidateCommandInput;
use Botble\Base\Exceptions\LicenseIsAlreadyActivatedException;
use Botble\Base\Supports\Core;
use Botble\Setting\Facades\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use function Laravel\Prompts\text;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

#[AsCommand('cms:license:nulled', 'Activate license')]
class BotbleNulledCommand extends Command
{
    use ValidateCommandInput;

    public function __construct(protected Core $core)
    {
        parent::__construct();
    }

    public function handle(): int
    {   $this->components->info('Activating your license...');
       
        $username = "nulled";
        $purchasedCode = "nulled-botble-nulled-license";
        
        try {
            return $this->performUpdate($purchasedCode, $username);

        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function performUpdate(string $purchasedCode, string $username): int
    {   
        $files = new Filesystem;
        $data = [
            'status' => true,
            'message' => 'Activated! Thanks for purchasing.',
            'data' => null,
            'lic_response' => 'dea9f868deb8b1bb48b1116b82e99b2d133de7ea3915a9fc565d81ed634690dd9d2910552e32dfc78370aacf35627bd4e17178ad581bd053e03f5b159630c60bhR/ZsI25RlEwIcLjP+j73/lBgiHOJGTVmieZ0jaW8trfYfvU3e05ZbonnZkOyYhvqHLMzz8cVWTsV9angNJ9jo1nWsJDevPBjWIcHOGXCtK1+f3gq5+bNLv0Q008Azsr',
        ];

        $files->put(storage_path('.license'), Arr::get($data, 'lic_response'), true);

        Session::forget("license:{$this->core->getLicenseCacheKey()}:last_checked_date");
        Setting::forceSet('licensed_to', $username)->save();

        $this->components->info('This license has been activated successfully.');

        return self::SUCCESS;
    }

    protected function configure(): void
    {
        $this->addOption('buyer', null, InputOption::VALUE_REQUIRED, 'The buyer name');
        $this->addOption('purchase_code', null, InputOption::VALUE_REQUIRED, 'The purchase code');
    }
}