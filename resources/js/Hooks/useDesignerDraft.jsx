import {
    prepareForSave,
    signature,
    toDraft,
    validateDraft,
} from '@/lib/designerDraft';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useMemo, useRef, useState } from 'react';

/**
 * Draft workspace for the interview designer: holds the whole structure in
 * memory, tracks dirtiness against the last saved signature, saves in one
 * request, and guards navigation while there are unsaved changes.
 *
 * A 409 from the save endpoint (deleting fields with recorded answers) is
 * surfaced as `detachPrompt` so the UI can ask for explicit confirmation and
 * retry with save({ confirmDetach: true }).
 */
export function useDesignerDraft({ initialStructure, saveUrl, guardMessage }) {
    const [sections, setSections] = useState(() => toDraft(initialStructure));
    const [savedSignature, setSavedSignature] = useState(() =>
        signature(toDraft(initialStructure)),
    );
    const [isSaving, setIsSaving] = useState(false);
    const [saved, setSaved] = useState(false);
    const [saveError, setSaveError] = useState(null);
    const [detachPrompt, setDetachPrompt] = useState(null);
    const [blockedMoves, setBlockedMoves] = useState(null);
    const savedTimeout = useRef(null);

    const isDirty = useMemo(
        () => signature(sections) !== savedSignature,
        [sections, savedSignature],
    );
    const issues = useMemo(() => validateDraft(sections), [sections]);

    useEffect(() => {
        if (!isDirty) {
            return undefined;
        }

        const handleBeforeUnload = (event) => {
            event.preventDefault();
            event.returnValue = '';
        };

        window.addEventListener('beforeunload', handleBeforeUnload);
        const removeInertiaGuard = router.on('before', (event) => {
            if (!window.confirm(guardMessage)) {
                event.preventDefault();
            }
        });

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            removeInertiaGuard();
        };
    }, [isDirty, guardMessage]);

    useEffect(
        () => () => {
            if (savedTimeout.current) {
                clearTimeout(savedTimeout.current);
            }
        },
        [],
    );

    const save = async ({ confirmDetach = false } = {}) => {
        if (issues.length > 0 || isSaving) {
            return false;
        }

        setIsSaving(true);
        setSaveError(null);
        setSaved(false);
        setBlockedMoves(null);

        try {
            const response = await axios.put(saveUrl, {
                sections: prepareForSave(sections),
                confirm_detach: confirmDetach,
            });

            const fresh = toDraft(response.data.structure);
            setSections(fresh);
            setSavedSignature(signature(fresh));
            setDetachPrompt(null);
            setSaved(true);
            savedTimeout.current = setTimeout(() => setSaved(false), 3000);

            return true;
        } catch (error) {
            const status = error.response?.status;
            const data = error.response?.data;

            if (status === 409 && data?.requires_confirmation) {
                setDetachPrompt({
                    detaching: data.detaching,
                    totalAnswers: data.total_answers,
                });
                return false;
            }

            if (status === 422 && Array.isArray(data?.items)) {
                setBlockedMoves(data.items);
                return false;
            }

            setSaveError(data?.message || error.message);
            return false;
        } finally {
            setIsSaving(false);
        }
    };

    return {
        blockedMoves,
        detachPrompt,
        isDirty,
        isSaving,
        issues,
        save,
        saveError,
        saved,
        sections,
        setBlockedMoves,
        setDetachPrompt,
        setSections,
    };
}
