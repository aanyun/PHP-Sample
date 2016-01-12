<?php
class CompanyController{
    /**
     * @api {post} /companies/new Add new company
     * @apiName Add a new company. 
     * @apiGroup Company
     * @apiDescription Create new company. 
     *         <p>After a new company is created, a company group will be auto generated. 
     *         The first member enrolled (actived) into the company group will be the owner of the company group.</p>
     *         <p>If all the information(name, address, city, zip_code, province) provided matches the existing data, 
     *         no new record will generate. Instead, the existing company id will return.</p> 
     * @apiParam  {String} name company's name.
     * @apiParam  {String} address company's address.
     * @apiParam  {String} country company's country.
     * @apiParam  {String} province company's city.
     * @apiParam  {String} city company's city.
     * @apiParam  {String} zip_code company's zip code.
     * 
     * @apiError 400 Input Invalid. This will happen if the param is missing or not in the valid format.
     * @apiError 409 Company name already exist.
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "name": "New Company",
     *       "address": "123 Test Way",
     *       "country": "United States",
     *       "city": "Chicago",
     *       "zip_code": "60606",
     *       "province": "Illinois",
     *       "id": 19
     *       }
     */   
    public static function addNew(){   
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();
        //validate input
        $validata = $app->validata;
        $validator = $validata::key('name', $validata::stringType()->notEmpty())
                                ->key('address', $validata::stringType()->notEmpty())
                                ->key('country', $validata::stringType()->notEmpty())
                                ->key('city', $validata::stringType()->notEmpty())
                                ->key('zip_code', $validata::stringType()->notEmpty())
                                ->key('province', $validata::stringType()->notEmpty());
        if (!$validator->validate((array)$data)){
            $app->halt(400,json_encode("Input Invalid"));
        }
        foreach ($data as $key => $value) {
            if(!in_array($key, ['name','address','country','city','province','zip_code'])){
                unset($data[$key]);
            }
        }
        
        if (self::isExist($data['name'])) {
        	$app->halt(409,json_encode("Compnay name already exists"));
        }
        
        $company = Company::firstOrNew($data);
        if(!$company->id){
            $company->save();
            $group = new Group();
            $group->company_id = $company->id;
            $group->name = $company->name;
            $group->access_code = self::generateRandomString(8);
            $group->save();
        }
        return json_encode($company);
    }

    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function getCompanyGroup($idCompany){
        return Group::where('company_id',$idCompany)->where('parent_id',0)->first();
    }

    /**
     * Check company if exist in our system
     *
     * @param  string  $name
     * @return Response
     */
    private static function isExist($name)
    {
    	return Company::where('name', '=', $name)->count();
    }

}
?>
