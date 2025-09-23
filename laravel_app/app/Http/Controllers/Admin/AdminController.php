<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Tableau de bord admin
     */
    public function index()
    {
        // TODO: injecter KPIs, cartes, etc.
        return view('modules.admin.index');
    }

    /**
     * Gestion des utilisateurs
     */
    public function users()
    {
        // TODO: récupérer la liste des users, pagination, etc.
        return view('modules.admin.users');
    }

    /**
     * Paramètres
     */
    public function settings()
    {
        // TODO: charger paramètres appli (env + DB)
        return view('modules.admin.settings');
    }
}
