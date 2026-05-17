import hmac

from fastapi import Depends, HTTPException, Request

from app.core.config import get_settings


async def verify_api_secret(request: Request):
    """Verify the X-API-Secret header matches the configured secret."""
    settings = get_settings()

    # Skip auth if no secret is configured (development mode)
    if not settings.api_secret:
        return

    # Health endpoint is public
    if request.url.path == "/health":
        return

    secret = request.headers.get("X-API-Secret", "")
    if not hmac.compare_digest(secret, settings.api_secret):
        raise HTTPException(status_code=401, detail="Invalid API secret")
