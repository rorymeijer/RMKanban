function FindProxyForURL(url, host) {

    // Check of proxy bereikbaar is (VPN actief)
    if (isResolvable("proxy.intern.local")) {

        // Microsoft login endpoints via proxy
        if (
            dnsDomainIs(host, "login.onmicrosoft.com") ||
            dnsDomainIs(host, "login.microsoft.com") ||
            dnsDomainIs(host, "login.microsoftonline.com")
        ) {
            return "PROXY proxy.intern.local:8080";
        }
    }

    // Alles anders direct
    return "DIRECT";
}
