#pragma once

#include "CoreMinimal.h"
#include "GameFramework/Character.h"
#include "RTSUnitBase.generated.h"

UCLASS()
class OPENXERTS_API ARTSUnitBase : public ACharacter
{
    GENERATED_BODY()

public:
    ARTSUnitBase();

    UPROPERTY(EditAnywhere, BlueprintReadWrite, Category="RTS|Team")
    int32 TeamId = 0;

    UPROPERTY(EditAnywhere, BlueprintReadWrite, Category="RTS|Stats")
    float MaxHealth = 100.f;

    UPROPERTY(VisibleAnywhere, BlueprintReadOnly, Category="RTS|Stats")
    float CurrentHealth = 100.f;

    UPROPERTY(EditAnywhere, BlueprintReadWrite, Category="RTS|Combat")
    float AttackRange = 300.f;

    UPROPERTY(EditAnywhere, BlueprintReadWrite, Category="RTS|Combat")
    float AttackDamage = 10.f;

    UPROPERTY(EditAnywhere, BlueprintReadWrite, Category="RTS|Combat")
    float AttackInterval = 1.0f;

    UFUNCTION(BlueprintCallable, Category="RTS|Commands")
    void IssueMove(const FVector& Destination);

    UFUNCTION(BlueprintCallable, Category="RTS|Commands")
    void IssueAttack(AActor* InTarget);

    UFUNCTION(BlueprintCallable, Category="RTS|Combat")
    void TakeRTSDamage(float DamageAmount);

protected:
    virtual void BeginPlay() override;
    virtual void Tick(float DeltaSeconds) override;

private:
    UPROPERTY()
    AActor* TargetActor = nullptr;

    float TimeSinceLastAttack = 0.f;

    void TryAttack(float DeltaSeconds);
};
