function FindProxyForURL(url, host) {

    // Als proxy bereikbaar is (dus VPN actief)
    if (isResolvable("proxy.intern.local")) {

        // Alleen login.onmicrosoft.com via proxy
        if (dnsDomainIs(host, "login.onmicrosoft.com")) {
            return "PROXY proxy.intern.local:8080";
        }
    }

    // Anders altijd direct
    return "DIRECT";
}
