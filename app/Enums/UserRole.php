<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Loket = 'loket';
    case Undian = 'undian';
    case Viewer = 'viewer';
}
