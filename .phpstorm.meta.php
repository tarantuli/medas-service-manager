<?php

namespace PHPSTORM_META {
    use Medas\ServiceManager\ServiceManager;

    override(ServiceManager::resolve(), map([
        '' => '@',
    ]));

    override(\service(), map([
        '' => '@',
    ]));
}
