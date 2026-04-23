package com.example.auth.controller;

public record RegisterRequest(
        String nom,
        String prenom,
        String email,
        String password,
        String role
) {
}
