<?php

namespace bootoffav\laravel\B24;

class Auth
{
    public function handle($request, \Closure $next)
    {
        // obtain new token (step 2)
        if ($request['code']) {
            $this->setCredentials(
                $this->getCredentials(
                'https://oauth.bitrix.info/oauth/token/?grant_type=authorization_code' .
                '&client_id=' . env('B24_CLIENT_ID') .
                '&client_secret=' . env('B24_CLIENT_SECRET') .
                '&code=' . $request['code']
                )                       
            );                          
            return back();              
        }

        // obtain new token (step 1)
        if (! $request->session()->has('b24_credentials')) {
            return redirect(env('B24_HOSTNAME').'/oauth/authorize/?client_id='.env('B24_CLIENT_ID'));
        }                                                                          
                                                                                   
        // refresh token                                                           
        if (time() > $request->session()->get('b24_credentials')->expires_at) {    
            $this->setCredentials(
                $this->getCredentials(
                    'https://oauth.bitrix.info/oauth/token/?grant_type=refresh_token' .
                    '&client_id=' . env('B24_CLIENT_ID') .
                    '&client_secret=' . env('B24_CLIENT_SECRET') .
                    '&refresh_token=' . session('b24_credentials')->refresh_token
                )                           
            );                              
            return back();                  
        }

        return $next($request);
    }

    private function getCredentials($request_str)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_str);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }

    private function setCredentials($cred)
    {
        $cred = json_decode($cred);
        $cred->expires_at = time() + $cred->expires_in;
        session()->put('b24_credentials', $cred);
    }
}

?>
