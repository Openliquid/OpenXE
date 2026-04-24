extends Node2D
class_name ResourceNode

@export var amount: float = 500.0

func harvest(requested: float) -> float:
    if amount <= 0.0:
        return 0.0
    var granted := min(amount, requested)
    amount -= granted
    if amount <= 0.0:
        queue_free()
    return granted
