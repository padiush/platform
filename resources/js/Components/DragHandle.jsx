import { faGripVertical } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

/**
 * Grip that arms drag-and-drop while pressed, so dragging can only start
 * from the handle — selecting text in a card's inputs never drags the card.
 * Pointer-only by design: keyboard and touch reordering go through
 * PositionSelect, so the handle is hidden from assistive tech.
 */
export default function DragHandle({ onArm, onDisarm, title }) {
    return (
        <span
            aria-hidden="true"
            title={title}
            className="btn btn-ghost btn-xs cursor-grab active:cursor-grabbing"
            onPointerDown={onArm}
            onPointerUp={onDisarm}
        >
            <FontAwesomeIcon icon={faGripVertical} />
        </span>
    );
}
