package com.example.auth.service;

import org.springframework.boot.context.properties.ConfigurationProperties;

@ConfigurationProperties(prefix = "auth.backend-sync")
public class BackendSyncProperties {

    private String baseUrl = "http://127.0.0.1:8000/api";
    private String syncSecret = "skillhub-sync-secret";

    public String getBaseUrl() {
        return baseUrl;
    }

    public void setBaseUrl(String baseUrl) {
        this.baseUrl = baseUrl;
    }

    public String getSyncSecret() {
        return syncSecret;
    }

    public void setSyncSecret(String syncSecret) {
        this.syncSecret = syncSecret;
    }
}
