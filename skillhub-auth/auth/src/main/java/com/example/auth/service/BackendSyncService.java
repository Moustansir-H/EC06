package com.example.auth.service;

import com.example.auth.exception.AuthenticationFailedException;
import lombok.RequiredArgsConstructor;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestClient;

import java.util.Map;

@Service
@RequiredArgsConstructor
public class BackendSyncService {

    private final BackendSyncProperties backendSyncProperties;

    public Long syncUser(String email, String nom, String prenom, String role, String password) {
        RestClient client = RestClient.create();
        String url = normalizeBaseUrl(backendSyncProperties.getBaseUrl()) + "/internal/users/sync";

        Map<String, Object> body = Map.of(
                "email", email,
                "nom", nom,
                "prenom", prenom,
                "role", role,
                "password", password
        );

        try {
            @SuppressWarnings("unchecked")
            Map<String, Object> response = client.post()
                    .uri(url)
                    .header("X-Auth-Sync-Secret", backendSyncProperties.getSyncSecret())
                    .contentType(MediaType.APPLICATION_JSON)
                    .body(body)
                    .retrieve()
                    .body(Map.class);

            if (response == null || response.get("id") == null) {
                throw new AuthenticationFailedException("Synchronisation utilisateur impossible");
            }

            Object id = response.get("id");
            if (id instanceof Number n) {
                return n.longValue();
            }

            return Long.parseLong(String.valueOf(id));
        } catch (Exception e) {
            throw new AuthenticationFailedException("Synchronisation utilisateur impossible");
        }
    }

    private String normalizeBaseUrl(String rawBaseUrl) {
        if (rawBaseUrl == null || rawBaseUrl.isBlank()) {
            return "http://127.0.0.1:8000/api";
        }

        String normalized = rawBaseUrl.trim();
        while (normalized.endsWith("/")) {
            normalized = normalized.substring(0, normalized.length() - 1);
        }
        return normalized;
    }
}
