<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2010 Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2024 Poweradmin Development Team
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use PoweradminInstall\InstallationHelper;
use PoweradminInstall\InstallationSteps;
use PoweradminInstall\LocaleHandler;
use PoweradminInstall\StepValidator;
use PoweradminInstall\TwigEnvironmentInitializer;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL) ;

class Installer
{
    private Request $request;
    private LocaleHandler $localeHandler;
    private StepValidator $stepValidator;
    private InstallationHelper $installationHelper;
    private string $localConfigFile;
    private string $defaultConfigFile;

    public function __construct()
    {
        $this->localConfigFile = dirname(__DIR__) . '/inc/config.inc.php';
        $this->defaultConfigFile = dirname(__DIR__) . '/inc/config-defaults.inc.php';
        $this->request = Request::createFromGlobals();
        $this->localeHandler = new LocaleHandler();
        $this->stepValidator = new StepValidator();
    }

    public function initialize(): void
    {
        $language = $this->request->get('language', LocaleHandler::DEFAULT_LANGUAGE);
        $currentLanguage = $this->localeHandler->getCurrentLanguage($language);
        $this->localeHandler->setLanguage($currentLanguage);

        $twigEnvironmentInitializer = new TwigEnvironmentInitializer($this->localeHandler);
        $twigEnvironment = $twigEnvironmentInitializer->initialize($currentLanguage);

        $step = $this->request->get('step', InstallationSteps::STEP_CHOOSE_LANGUAGE);
        $currentStep = $this->stepValidator->getCurrentStep($step);

        $this->installationHelper = new InstallationHelper($this->request, $twigEnvironment, $currentStep, $currentLanguage);
        $this->installationHelper->checkConfigFile($this->localConfigFile);

        $this->handleStep($currentStep);
    }

    private function handleStep($currentStep): void
    {
        switch ($currentStep) {
            case InstallationSteps::STEP_CHOOSE_LANGUAGE:
                $this->installationHelper->step1ChooseLanguage();
                break;

            case InstallationSteps::STEP_GETTING_READY:
                $this->installationHelper->step2GettingReady();
                break;

            case InstallationSteps::STEP_CONFIGURING_DATABASE:
                $this->installationHelper->step3ConfiguringDatabase();
                break;

            case InstallationSteps::STEP_SETUP_ACCOUNT_AND_NAMESERVERS:
                $this->installationHelper->step4SetupAccountAndNameServers($this->defaultConfigFile);
                break;

            case InstallationSteps::STEP_CREATE_LIMITED_RIGHTS_USER:
                $this->installationHelper->step5CreateLimitedRightsUser();
                break;

            case InstallationSteps::STEP_CREATE_CONFIGURATION_FILE:
                $this->installationHelper->step6CreateConfigurationFile(
                    $this->defaultConfigFile,
                    $this->localConfigFile
                );
                break;

            case InstallationSteps::STEP_INSTALLATION_COMPLETE:
                $this->installationHelper->step7InstallationComplete();
                break;

            default:
                break;
        }
    }
}

$installer = new Installer();
$installer->initialize();
