package com.example.auth.controller;

import com.example.auth.entity.User;
import com.example.auth.service.AuthService;
import com.example.auth.service.LoginResult;
import lombok.RequiredArgsConstructor;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.Map;

@RestController
@RequestMapping("/api/auth")
@RequiredArgsConstructor
public class AuthController {

    private static final String EMAIL_KEY = "email";
    private static final String MESSAGE_KEY = "message";
    private static final String TOKEN_KEY = "access_token";
    private static final String TOKEN_ALIAS_KEY = "accessToken";
    private static final String TOKEN_TYPE_KEY = "token_type";
    private static final String EXPIRES_AT_KEY = "expires_at";
    private static final String EXPIRES_AT_ALIAS_KEY = "expiresAt";

    private final AuthService authService;

    @PostMapping("/register")
        public ResponseEntity<Map<String, Object>> register(@RequestBody RegisterRequest request) {
        User user = authService.register(
            request.nom(),
            request.prenom(),
            request.email(),
            request.password(),
            request.role()
        );

        return new ResponseEntity<>(Map.of(
            "id", user.getId(),
            "nom", user.getNom(),
            "prenom", user.getPrenom(),
            "role", user.getRole(),
            EMAIL_KEY, user.getEmail(),
            "backendUserId", user.getBackendUserId(),
            "createdAt", user.getCreatedAt()
        ), HttpStatus.CREATED);
    }

    @PostMapping("/login")
    public ResponseEntity<Map<String, Object>> login(@RequestBody LoginProofRequest request) {
        LoginResult login = authService.loginWithProof(
                request.email(),
                request.nonce(),
                request.timestamp(),
                request.hmac()
        );

        return ResponseEntity.ok(Map.of(
                MESSAGE_KEY, "Connexion réussie",
                EMAIL_KEY, login.email(),
                TOKEN_TYPE_KEY, "bearer",
                TOKEN_KEY, login.accessToken(),
                TOKEN_ALIAS_KEY, login.accessToken(),
                EXPIRES_AT_KEY, login.expiresAt(),
                EXPIRES_AT_ALIAS_KEY, login.expiresAt(),
                "user", Map.of(
                        "id", login.backendUserId(),
                        "email", login.email(),
                        "role", login.role(),
                        "nom", login.nom(),
                        "prenom", login.prenom()
                )
        ));
    }
}
