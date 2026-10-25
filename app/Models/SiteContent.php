<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One edited website field, e.g. page "home", key "hero.title". See config/site-content.php.
 */
#[Fillable(['page', 'key', 'value'])]
class SiteContent extends Model {}
