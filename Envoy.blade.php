@setup
    // check for required --branch command line param
    if (! preg_match('/^(master|develop|feature\/OGT-[a-z0-9-]+)$/', $branch)) {
        exit("The command line parameter '--branch' is required.\n");
    }

    $servers = [
        'test' => '',
        'production' => '',
    ];

    // check for required server --env command line param
    $validServerEnvs = array_keys($servers);

    if (! in_array($env, $validServerEnvs)) {
        exit("The command line parameter '--env' with a value from [" . implode(', ', $validServerEnvs) . "] is required.\n");
    }

    $envNameUppercase = strtoupper($env);

    // check for required project env params for deployment
    $requiredEnvVars = [
        'APP_NAME',
        'DEPLOY_APP_URL_' . $envNameUppercase,
        'DEPLOY_APP_DIR',
        'DEPLOY_HOST_' . $envNameUppercase,
        'DEPLOY_REPO',
        'DEPLOY_SLACK_WEBHOOK',
        'DEPLOY_SLACK_WEBHOOK_ENABLED',
        'DEPLOY_USER_' . $envNameUppercase,
    ];

    try {
        require __DIR__ . '/vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
        $dotenv->required($requiredEnvVars)->notEmpty();
    } catch ( Exception $e )  {
        exit("Exception: " . $e->getMessage() . "\n");
    }

    // set project deployment params
    $deployUser = env('DEPLOY_USER_' . $envNameUppercase);
    $servers[$env] = $deployUser . '@' . env('DEPLOY_HOST_' . $envNameUppercase);

    $repository = env('DEPLOY_REPO');
    $appDir = env('DEPLOY_APP_DIR') . '/' . env('APP_NAME');
    $appUrl = str_replace('/', '\/', env('DEPLOY_APP_URL_' . $envNameUppercase));
    $releasesDir = $appDir . '/releases';
    $releaseDate = date('Y-m-d_H-i-s');
    $newReleaseDir = $releasesDir . '/' . $releaseDate;

    $slackWebhook = env('DEPLOY_SLACK_WEBHOOK');
    $isSlackWebhookEnabled = env('DEPLOY_SLACK_WEBHOOK_ENABLED');

    function logConsole($message) {
        return "echo '\033[32m###### " . $message . " ######\033[0m';\n";
    }
@endsetup

@servers($servers)

@story('setup_system', ['on' => $env])
    update_system
    install_packages
@endstory

@story('deploy', ['on' => $env])
    clone_repository
    run_composer
    init_env
    run_npm
    update_symlinks
@endstory

@task('update_system')
    {{ logConsole('Update the package sources list to get the latest list of available packages in the repositories:') }}
    sudo apt update -y

    {{ logConsole("Add Ondřej Surý PHP repository - https://deb.sury.org/:") }}
    # https://github.com/oerdnj/deb.sury.org/wiki/Frequently-Asked-Questions#how-to-enable-the-debsuryorg-repository
    sudo apt -y install apt-transport-https lsb-release ca-certificates curl
    sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
    sudo sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
    sudo apt update -y
@endtask

@task('install_packages')
    # @todo Install basic packages like git, ... (pre-installed on the test server)
    # @todo Install NGINX packages (pre-installed on the test server)
    # @todo Create NGINX server config for project (created directly on the test server)
    # @todo Update npm

    {{ logConsole("Install required PHP packages for NGINX:") }}
    # includes php-common php7.4-cli php7.4-common php7.4-fpm php7.4-json php7.4-opcache php7.4-readline
    # includes PHP modules (php -m) for e.g. ctype fileinfo json openssl pdo tokenizer
    sudo apt -y install php7.4-fpm

    {{ logConsole("Install additionally required PHP packages for Laravel:") }}
    # https://laravel.com/docs/8.x/deployment#server-requirements
    sudo apt -y install php7.4-bcmath php7.4-mbstring php7.4-xml

    {{ logConsole("Install PHP dependency manager Composer for Laravel:") }}
    php -r "readfile('https://getcomposer.org/installer');" | sudo php -- --install-dir=/usr/local/bin/ --filename=composer

    {{ logConsole("Install Node.js package manager npm for Laravel / Vue.js:") }}
    curl -sL https://deb.nodesource.com/setup_16.x | sudo bash -
    sudo apt -y install nodejs
@endtask

@task('clone_repository')
    # @todo Clone a certain branch or tag of repository

    {{ logConsole("Cloning repository:") }}
    test -d {{ $releasesDir }} || sudo mkdir -p {{ $releasesDir }}
    sudo chown -R {{ $deployUser }}:{{ $deployUser }} {{ $appDir }}

    eval $(ssh-agent -s)
    ssh-add ~/.ssh/id_ed25519_{{ $deployUser }}

    git clone --depth 1 --branch {{ $branch }} {{ $repository }} {{ $newReleaseDir }}
@endtask

@task('run_composer')
    {{ logConsole("Run composer") }}
    cd {{ $newReleaseDir }}
    composer install --optimize-autoloader --no-dev
@endtask

@task('init_env')
    {{ logConsole("Init env") }}
    cd {{ $newReleaseDir }}
    cp .env.example .env
    php artisan key:generate

    sed -i -E "s/^ASSET_URL=.*$/ASSET_URL={{ $appUrl }}/g" .env

    # @todo required?
    # php artisan config:cache
@endtask

@task('run_npm')
    {{ logConsole("Run NPM to install all dependencies of the project in package-lock.json...") }}
    cd {{ $newReleaseDir }}
    npm install --production

    {{ logConsole("Run all Mix tasks and minify output...") }}
    # https://laravel.com/docs/8.x/mix#running-mix
    npm run prod
@endtask

@task('update_symlinks')
    {{ logConsole("Prepare storage directory") }}
    test -d {{ $appDir }}/storage/framework/sessions || sudo mkdir -p {{ $appDir }}/storage/framework/sessions
    test -d {{ $appDir }}/storage/framework/views || sudo mkdir -p {{ $appDir }}/storage/framework/views
    sudo chown -R www-data:www-data {{ $appDir }}/storage

    {{ logConsole("Linking storage directory") }}
    rm -rf {{ $newReleaseDir }}/storage
    ln -nfs {{ $appDir }}/storage {{ $newReleaseDir }}/storage

    {{ logConsole("Linking current release") }}
    ln -nfs {{ $newReleaseDir }} {{ $appDir }}/current
@endtask
