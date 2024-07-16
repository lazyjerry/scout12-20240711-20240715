<?php

namespace App\Validation;

class ApiAuthRules
{
    public function validateAuthPassword(string $str, string $fields, array $data): bool
    {
        // try {
        //     $user_model = new UserModel();
        //     $obj = $user_model->where('email',$data['email'])->first();
        //     $phpass = new PasswordHash(8, true);
        //     return $phpass->CheckPassword($data['password']??'', $obj['password']);
        // } catch (Exception $e) {
        //     return false;
        // }
    }

    public function validateAuthPermission(string $str, string $fields, array $data): bool
    {
        // try {
        //     $user_model = new UserModel();
        //     $settings_model = new SettingsModel();
        //     $settings = $settings_model->first()??[];
        //     $obj = $user_model->where('email',$data['email'])->first();
        //     return $settings['group_api'] == $obj['group'];
        // } catch (Exception $e) {
        //     return false;
        // }
    }
}
