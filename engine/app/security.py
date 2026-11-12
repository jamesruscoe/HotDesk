import hmac
import os

from fastapi import Header, HTTPException


def require_token(authorization: str = Header(default="")) -> None:
    expected = os.environ.get("ENGINE_TOKEN", "")
    # Fail closed: an engine deployed without a token rejects everything.
    if not expected or not hmac.compare_digest(authorization, f"Bearer {expected}"):
        raise HTTPException(status_code=401, detail="Invalid engine token.")
