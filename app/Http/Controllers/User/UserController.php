<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Mail\UserCreated;
use App\User;
use Illuminate\Support\Facades\Mail;

class UserController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $usuarios = User::all();

        return $this->showAll($usuarios); //response()->json(['data' => $usuarios], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed'
        ];

        $this->validate($request, $rules);
        
        $campos = $request->all();
        $campos['password'] = bcrypt($request->password);
        $campos['verified'] = User::USUARIO_NO_VERIFICADO;
        $campos['verification_token'] = User::generarVerificationToken();
        $campos['admin'] = User::USUARIO_REGULAR;

        $usuario = User::create($campos);
        return  $this->showOne($usuario, 201); //response()->json(['data' => $usuario], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return $this->showOne($user, 200); //response()->json(['data' => $usuario], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {

        $rules = [
            'email' => 'email|unique:users,email,' . $user->id,
            'password' => 'min:6|confirmed',
            'admin' => 'in:' . User::USUARIO_ADMINISTRADOR . ',' . User::USUARIO_REGULAR, 
        ];

        $this->validate($request, $rules);

        if ($request->has('name')) 
        {
            $user->name = $request->name;
        }

        if ($request->has('email') && $request->email != $user->email) 
        {
            $user->verified = User::USUARIO_NO_VERIFICADO;
            $user->verification_token = User::generarVerificationToken();
            $user->email = $request->email;
        }

        if ($request->has('password')) 
        {
            $user->password = bcrypt($request->password);
        }

        if ($request->has('admin')) 
        {
            if(!$user->esVerificado())
            {
                return $this->errorResponse('Unicamente los usuarios verficados pueden cambiar su valor de administrador', 409);
            }

            $user->admin = $request->admin;
        }

        if(!$user->isDirty())
        {
            return $this->errorResponse('Se debe especificar al menos un valor diferente para actualizar', 422);
        }

        $user->save();

        return $this->showOne($user, 200);//response()->json(['data' => $user], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        return $this->showOne($user, 200);
    }

    public function verify($token)
    {
        $user = User::where('verification_token', $token)->firstOrFail();
        $user->verified = User::USUARIO_VERIFICADO;
        $user->verification_token = null;

        $user->save();

        return $this->showMessage('La cuenta ha sido verificada');
    }

    public function resend(User $user)
    {
        if($user->esVerificado())
        {
            return $this->errorResponse('Este usuario ya ha sido verificado.', 409);
        }

        retry(5, function() use($user){
            Mail::to($user)->send(new UserCreated($user));
        }, 100);

        return $this->showMessage('El email de verificación se ha reenviado');
    }
}
