<?php
            namespace App\adminmodel;
            use Illuminate\Contracts\Auth\MustVerifyEmail;
            use Illuminate\Database\Eloquent\Factories\HasFactory;
            use Illuminate\Foundation\Auth\User as Authenticatable;
            use Illuminate\Notifications\Notifiable;
            use Laravel\Sanctum\HasApiTokens;
            use Illuminate\Database\Eloquent\SoftDeletes;
            class FyersModal extends Authenticatable
            {

                protected $table='fyers';
                use HasApiTokens, HasFactory, Notifiable;
                /**
                 * The attributes that are mass assignable.
                 *
                 * @var array<int, string>
                 */
                protected $fillable = [
                	 'phone',
	                 'login_id',
	                 'pin',
	                 'lots',
	                 'lots2',
	                 'lots3',
	                 'option_ce',
	                 'option_pe',
                     'bankoption_ce',
	                 'bankoption_pe',
                     'stockoption_ce',
	                 'stockoption_pe',
	                 'trading_type',
                     'ip', 'added_by', 'is_active'
                ];
                use SoftDeletes;
                protected $del = ['deleted_at'];
                /**
                 * The attributes that should be hidden for serialization.
                 *
                 * @var array<int, string>
                 */
               
                /**
                 * The attributes that should be cast.
                 *
                 * @var array<string, string>
                 */
                protected $casts = [
                    'email_verified_at' => 'datetime',
                ];
            }
        