<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class InternalUserSyncController extends Controller
{
    public function sync(Request $request)
    {
        $expectedSecret = (string) env('AUTH_INTERNAL_SYNC_SECRET', 'skillhub-sync-secret');
        $providedSecret = (string) $request->header('X-Auth-Sync-Secret', '');

        if ($providedSecret === '' || ! hash_equals($expectedSecret, $providedSecret)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'role' => 'required|in:APPRENANT,FORMATEUR,ADMINISTRATEUR',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            $user->nom = $request->input('nom');
            $user->prenom = $request->input('prenom');
            $user->role = $request->input('role');

            if ($request->filled('password')) {
                $user->password = Hash::make((string) $request->input('password'));
            }

            $user->save();
        } else {
            $user = User::create([
                'email' => $request->input('email'),
                'nom' => $request->input('nom'),
                'prenom' => $request->input('prenom'),
                'role' => $request->input('role'),
                'password' => Hash::make((string) $request->input('password', str()->random(32))),
            ]);
        }

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
        ]);
    }
}
