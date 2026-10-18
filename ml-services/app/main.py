
from datetime import date
from pathlib import Path
from typing import Literal

import joblib
import pandas as pd

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field, model_validator


from decimal import Decimal, ROUND_HALF_UP

from app.fuzzy_pricing import calculate_fuzzy_adjustment


ROOT = Path(__file__).resolve().parents[1]
MODEL_PATH = ROOT / "models" / "demand_model.joblib"

app = FastAPI(
    title="Kareem Pricing ML Service",
    version="0.1.0",
)

model = (
    joblib.load(MODEL_PATH)
    if MODEL_PATH.exists()
    else None
)


class DemandRequest(BaseModel):
    participants: int = Field(ge=1, le=100000)
    booking_date: date
    event_date: date
    event_category: Literal[
        "wedding",
        "corporate",
        "concert",
        "gathering",
    ]

    @model_validator(mode="after")
    def validate_dates(self):
        if self.event_date <= self.booking_date:
            raise ValueError(
                "Tanggal event harus setelah tanggal booking."
            )
        return self


@app.get("/health")
def health():
    return {
        "status": "ok" if model is not None else "model_unavailable",
        "model_loaded": model is not None,
    }


@app.post("/predict-demand")
def predict_demand(payload: DemandRequest):
    if model is None:
        raise HTTPException(
            status_code=503,
            detail="Model belum tersedia. Jalankan training.",
        )

    lead_days = (
        payload.event_date - payload.booking_date
    ).days

    features = pd.DataFrame([{
        "participants": payload.participants,
        "lead_days": lead_days,
        "event_month": payload.event_date.month,
        "event_category": payload.event_category,
    }])

    prediction = float(model.predict(features)[0])
    score = max(0.0, min(1.0, prediction))

    if score < 0.4:
        level = "low"
    elif score < 0.7:
        level = "medium"
    else:
        level = "high"

    return {
        "success": True,
        "model_version": "rf-synthetic-v1",
        "predicted_demand": round(score, 4),
        "demand_level": level,
        "lead_days": lead_days,
    }

class PriceRequest(DemandRequest):
    base_price: float = Field(gt=0, le=1000000000000)


@app.post("/predict-price")
def predict_price(payload: PriceRequest):
    if model is None:
        raise HTTPException(
            status_code=503,
            detail="Model belum tersedia. Jalankan training.",
        )

    lead_days = (
        payload.event_date - payload.booking_date
    ).days

    features = pd.DataFrame([{
        "participants": payload.participants,
        "lead_days": lead_days,
        "event_month": payload.event_date.month,
        "event_category": payload.event_category,
    }])

    prediction = float(model.predict(features)[0])
    demand_score = max(0.0, min(1.0, prediction))

    fuzzy = calculate_fuzzy_adjustment(
        demand_score=demand_score,
        lead_days=lead_days,
    )

    adjustment = Decimal(
        str(fuzzy["adjustment_percent"])
    )

    base_price = Decimal(str(payload.base_price))

    recommended_price = (
        base_price * (Decimal("1") + adjustment / Decimal("100"))
    ).quantize(
        Decimal("0.01"),
        rounding=ROUND_HALF_UP,
    )

    return {
        "success": True,
        "model_version": "rf-synthetic-v1",
        "calculation_method": "random_forest_fuzzy_sugeno",
        "base_price": float(base_price),
        "predicted_demand": round(demand_score, 4),
        "fuzzy_adjustment_percent": float(adjustment),
        "recommended_price": float(recommended_price),
        "input_features": {
            "participants": payload.participants,
            "lead_days": lead_days,
            "event_month": payload.event_date.month,
            "event_category": payload.event_category,
        },
        "calculation_details": fuzzy,
    }
