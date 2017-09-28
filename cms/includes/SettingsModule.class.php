<?php

class SettingsModule extends AbstractModule {
	protected $hint = '<p>Все параметры сохраняются автоматически.</p>';

    function handleAjaxRequest( $uri ) {
        try {

            $names = Meta::getUriPathNames( $uri );

            switch( $names[0] ) {
                case 'module':
                    $vars = Meta::vars();

                    $user = Session::getInstance()->getUser();
                    $user->start_module = (int)$vars['module'] > 0 ? (int)$vars['module'] : null;
                    $user->save();
                    
                    Session::getInstance()->reloadData();

                    return json_encode( array( 'success' => true ) );
            }
        }
        catch( Exception $e ) {
            return json_encode( array( 'success' => false, 'message' => $e->getMessage() ) );
        }

        return null;
    }
}

