from pathlib import Path
from dotenv import load_dotenv
from datetime import date
from typing import Literal
import joblib
import pandas as pd
from fastapi import FastAPI, HTTPException, Header
from pydantic import BaseModel, Field
from features import make_features, FEATURES
import os


load_dotenv(
    dotenv_path=Path(__file__).resolve().parent / ".env"
)

print("ML API key configured:", bool(os.getenv("ML_API_KEY")))
ROOT = Path(__file__).resolve().parent
MODEL_PATH = ROOT / 'random_forest.joblib'
app = FastAPI(title='Kareem Pricing AI', version='1.0.0')
model = joblib.load(MODEL_PATH) if MODEL_PATH.exists() else None

class PredictionInput(BaseModel):
    jenis_acara: Literal['Workshop', 'Seminar', 'Family Gathering', 'Employee Gathering', 'Event Bisnis']
    tanggal_pelaksanaan: date
    base_price: float = Field(gt=0)

# Sugeno orde-nol: output tiap aturan adalah konstanta persen.
# Nilai berikut merupakan asumsi bisnis prototipe, BUKAN hasil pembelajaran dataset.
def fuzzy_sugeno(features):
    weekend = features['kategori_hari'] == 'Weekend'
    # Dua aturan berbobot: faktor hari dan faktor kategori.
    day_adjustment = 3.0 if weekend else 0.0
    category_adjustment = {
        'Workshop': 0.0, 'Seminar': 0.5, 'Family Gathering': 1.0,
        'Employee Gathering': 1.0, 'Event Bisnis': 1.5
    }[features['jenis_acara']]
    weights = (0.6, 0.4)
    return round((weights[0]*day_adjustment + weights[1]*category_adjustment) / sum(weights), 4)

@app.get('/health')
def health():
    return {'status': 'ok' if model is not None else 'model_not_trained'}

@app.post('/predict')
def predict(payload: PredictionInput, x_api_key: str | None = Header(default=None)):
    secret = os.getenv('ML_API_KEY')
    if not secret or x_api_key != secret:
        raise HTTPException(status_code=401, detail='Unauthorized')
    if model is None:
        raise HTTPException(status_code=503, detail='Jalankan train.py dahulu')
    features = make_features(payload.jenis_acara, payload.tanggal_pelaksanaan.isoformat())
    rf_price = float(model.predict(pd.DataFrame([features])[FEATURES])[0])
    adjustment = fuzzy_sugeno(features)
    final_price = round(rf_price * (1 + adjustment/100), 2)
    return {'base_price': payload.base_price, 'rf_predicted_price': round(rf_price, 2),
            'predicted_demand': None, 'fuzzy_adjustment_percent': adjustment,
            'recommended_price': final_price, 'model_version': 'rf-60-v1',
            'calculation_method': 'random_forest_fuzzy_sugeno',
            'input_features': features,
            'calculation_details': {'fuzzy_type': 'sugeno_zero_order', 'fuzzy_rule_source': 'prototype_business_assumptions',
                                    'formula': 'rf_predicted_price * (1 + fuzzy_adjustment_percent / 100)'}}
