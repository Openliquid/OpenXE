#pragma once

#include "CoreMinimal.h"
#include "GameFramework/PlayerController.h"
#include "RTSPlayerController.generated.h"

class ARTSUnitBase;

UCLASS()
class OPENXERTS_API ARTSPlayerController : public APlayerController
{
    GENERATED_BODY()

public:
    ARTSPlayerController();

protected:
    virtual void SetupInputComponent() override;

private:
    UPROPERTY()
    TArray<ARTSUnitBase*> SelectedUnits;

    void HandleLeftClick();
    void HandleRightClick();
    void SelectSingleUnderCursor();
    void CommandMoveOrAttackUnderCursor();
};
