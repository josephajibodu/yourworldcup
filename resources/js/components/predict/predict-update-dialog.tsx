import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface PredictUpdateDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
    processing?: boolean;
}

export function PredictUpdateDialog({
    open,
    onOpenChange,
    onConfirm,
    processing = false,
}: PredictUpdateDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader className="text-center sm:text-center">
                    <DialogTitle className="font-display text-2xl tracking-wide">
                        update your picks?
                    </DialogTitle>
                    <DialogDescription className="text-base leading-relaxed">
                        You already saved picks for some of today&apos;s
                        matches. Saving now will replace those selections with
                        what you have on screen.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter className="flex-col gap-2 sm:flex-col">
                    <Button
                        type="button"
                        variant="gold"
                        size="lg"
                        className="w-full rounded-full"
                        disabled={processing}
                        onClick={onConfirm}
                        data-test="predict-update-confirm-button"
                    >
                        Update my picks
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="lg"
                        className="w-full rounded-full"
                        disabled={processing}
                        onClick={() => onOpenChange(false)}
                    >
                        Keep editing
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
