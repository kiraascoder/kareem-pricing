
from pathlib import Path
import json

import joblib
import numpy as np
import pandas as pd

from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder

ROOT = Path(__file__).resolve().parents[1]
MODEL_DIR = ROOT / "models"
MODEL_DIR.mkdir(parents=True, exist_ok=True)

RNG = np.random.default_rng(42)
N = 3000

categories = ["wedding", "corporate", "concert", "gathering"]

df = pd.DataFrame({
    "participants": RNG.integers(20, 2001, N),
    "lead_days": RNG.integers(1, 366, N),
    "event_month": RNG.integers(1, 13, N),
    "event_category": RNG.choice(categories, N),
})

category_effect = df["event_category"].map({
    "wedding": 0.12,
    "corporate": 0.08,
    "concert": 0.18,
    "gathering": 0.05,
}).to_numpy()

season_effect = np.where(
    df["event_month"].isin([6, 7, 12]),
    0.12,
    0.0,
)

participant_effect = (
    df["participants"].to_numpy() / 2000
) * 0.30

lead_effect = np.clip(
    (90 - df["lead_days"].to_numpy()) / 90,
    0,
    1,
) * 0.15

noise = RNG.normal(0, 0.04, N)

demand = (
    0.15
    + category_effect
    + season_effect
    + participant_effect
    + lead_effect
    + noise
)

df["demand_score"] = np.clip(demand, 0, 1)

features = [
    "participants",
    "lead_days",
    "event_month",
    "event_category",
]

X = df[features]
y = df["demand_score"]

X_train, X_test, y_train, y_test = train_test_split(
    X,
    y,
    test_size=0.2,
    random_state=42,
)

preprocessor = ColumnTransformer([
    (
        "category",
        OneHotEncoder(handle_unknown="ignore"),
        ["event_category"],
    ),
], remainder="passthrough")

model = Pipeline([
    ("preprocessor", preprocessor),
    ("regressor", RandomForestRegressor(
        n_estimators=200,
        max_depth=12,
        min_samples_leaf=3,
        random_state=42,
        n_jobs=-1,
    )),
])

model.fit(X_train, y_train)

predictions = model.predict(X_test)

metrics = {
    "mae": float(mean_absolute_error(y_test, predictions)),
    "rmse": float(np.sqrt(mean_squared_error(y_test, predictions))),
    "r2": float(r2_score(y_test, predictions)),
    "train_samples": len(X_train),
    "test_samples": len(X_test),
    "model_version": "rf-synthetic-v1",
    "dataset_type": "synthetic",
}

joblib.dump(model, MODEL_DIR / "demand_model.joblib")

(MODEL_DIR / "metrics.json").write_text(
    json.dumps(metrics, indent=2),
    encoding="utf-8",
)

print(json.dumps(metrics, indent=2))
print("Model berhasil disimpan.")
