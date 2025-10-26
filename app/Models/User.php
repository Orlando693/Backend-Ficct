<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'phone',
        'role',
        'status',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'must_change_password'=> 'boolean',
        ];
    }

    // Helper: mapea al shape del frontend
    public function toFrontend(): array
    {
        return [
            'id'        => $this->id,
            'nombre'    => $this->name,
            'username'  => $this->username,
            'correo'    => $this->email,
            'telefono'  => $this->phone,
            'rol'       => $this->role,      // "Decanato" | "CPD" | "Jefatura" | "Docente"
            'estado'    => $this->status,    // "ACTIVO" | "BLOQUEADO" | "PENDIENTE" | "INACTIVO"
            'creado'    => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
