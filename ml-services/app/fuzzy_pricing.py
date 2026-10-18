
from typing import Any


def triangular(
    value: float,
    left: float,
    center: float,
    right: float,
) -> float:
    if value == center:
        return 1.0

    if value <= left or value >= right:
        return 0.0

    if value < center:
        return (value - left) / (center - left)

    return (right - value) / (right - center)


def left_shoulder(
    value: float,
    start: float,
    end: float,
) -> float:
    if value <= start:
        return 1.0

    if value >= end:
        return 0.0

    return (end - value) / (end - start)


def right_shoulder(
    value: float,
    start: float,
    end: float,
) -> float:
    if value <= start:
        return 0.0

    if value >= end:
        return 1.0

    return (value - start) / (end - start)


def calculate_fuzzy_adjustment(
    demand_score: float,
    lead_days: int,
) -> dict[str, Any]:

    if not 0 <= demand_score <= 1:
        raise ValueError("Demand score harus antara 0 dan 1.")

    if lead_days < 1:
        raise ValueError("Lead days minimal 1.")

    demand = {
        "low": left_shoulder(demand_score, 0.25, 0.50),
        "medium": triangular(demand_score, 0.25, 0.50, 0.75),
        "high": right_shoulder(demand_score, 0.50, 0.75),
    }

    booking_time = {
        "short": left_shoulder(lead_days, 14, 45),
        "medium": triangular(lead_days, 14, 60, 120),
        "long": right_shoulder(lead_days, 60, 120),
    }

    rules = [
        ("low", "short", 0.0),
        ("low", "medium", -5.0),
        ("low", "long", -10.0),

        ("medium", "short", 8.0),
        ("medium", "medium", 3.0),
        ("medium", "long", 0.0),

        ("high", "short", 20.0),
        ("high", "medium", 15.0),
        ("high", "long", 10.0),
    ]

    weighted_sum = 0.0
    total_weight = 0.0
    activated_rules = []

    for demand_label, time_label, adjustment in rules:
        weight = min(
            demand[demand_label],
            booking_time[time_label],
        )

        if weight <= 0:
            continue

        weighted_sum += weight * adjustment
        total_weight += weight

        activated_rules.append({
            "demand": demand_label,
            "booking_time": time_label,
            "weight": round(weight, 4),
            "adjustment_percent": adjustment,
        })

    if total_weight == 0:
        raise ValueError("Tidak ada aturan fuzzy yang aktif.")

    adjustment_percent = weighted_sum / total_weight

    return {
        "adjustment_percent": round(adjustment_percent, 4),
        "demand_membership": demand,
        "time_membership": booking_time,
        "activated_rules": activated_rules,
    }
