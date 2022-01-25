<?php

namespace PHPSTORM_META {
    use Medas\ServiceManager\ServiceManager;

    override(ServiceManager::resolve(), map([
        '' => '@',
    ]));

    override(ServiceManager::instantiate(), map([
        '' => '@',
    ]));

    override(\service(), map([
        '' => '@',
    ]));

    override(\attribute(), map([
        '' => '@',
    ]));
}
