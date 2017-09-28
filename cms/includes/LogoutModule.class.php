<?php

class LogoutModule extends AbstractModule {
    public function handleHtmlRequest() {
            Session::getInstance()->logout();
            header( "Location: {$this->cmsUri}/", true );
            exit;
    }
}

